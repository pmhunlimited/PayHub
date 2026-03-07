import express from "express";
import { createServer as createViteServer } from "vite";
import path from "path";
import db from "./src/db.ts";
import dotenv from "dotenv";

dotenv.config();

async function startServer() {
  const app = express();
  const PORT = 3000;

  app.use(express.json());

  // API Routes
  app.get("/api/health", (req, res) => {
    res.json({ status: "ok" });
  });

  // Auth API
  app.post("/api/auth/register", (req, res) => {
    const { email, password, full_name, business_name } = req.body;
    try {
      const stmt = db.prepare("INSERT INTO users (email, password_hash, full_name, business_name) VALUES (?, ?, ?, ?)");
      const result = stmt.run(email, password, full_name, business_name); // In real app, hash password
      res.json({ id: result.lastInsertRowid, email });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.post("/api/auth/login", (req, res) => {
    const { email, password } = req.body;
    try {
      const user = db.prepare("SELECT * FROM users WHERE email = ?").get(email) as any;
      if (user && user.password_hash === password) { // In real app, verify hash
        if (user.is_suspended) {
          return res.status(403).json({ error: "Your account has been suspended. Please contact support." });
        }
        res.json({ id: user.id, email: user.email, role: user.role, business_name: user.business_name, is_kyc_verified: user.is_kyc_verified });
      } else {
        res.status(401).json({ error: "Invalid credentials" });
      }
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.post("/api/auth/forgot-password", (req, res) => {
    const { email } = req.body;
    const user = db.prepare("SELECT * FROM users WHERE email = ?").get(email) as any;
    if (user) {
      // In a real app, generate a token and send an email
      console.log(`Password reset requested for ${email}`);
      res.json({ success: true, message: "Reset link sent" });
    } else {
      // For security, don't reveal if user exists
      res.json({ success: true, message: "Reset link sent" });
    }
  });

  app.post("/api/auth/reset-password", (req, res) => {
    const { token, password } = req.body;
    // In a real app, verify the token and find the user
    // For this demo, we'll just simulate success if a token is provided
    if (token) {
      // db.prepare("UPDATE users SET password_hash = ? WHERE id = ?").run(password, userId);
      res.json({ success: true, message: "Password reset successful" });
    } else {
      res.status(400).json({ error: "Invalid or expired token" });
    }
  });

  // Merchant API
  app.get("/api/merchant/stats/:userId", (req, res) => {
    const { userId } = req.params;
    const stats = db.prepare(`
      SELECT 
        SUM(amount) as total_volume,
        COUNT(*) as transaction_count,
        (SELECT wallet_balance FROM users WHERE id = ?) as balance
      FROM transactions 
      WHERE user_id = ? AND status = 'success'
    `).get(userId, userId) as any;

    const total = db.prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?").get(userId) as any;
    const success = db.prepare("SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND status = 'success'").get(userId) as any;
    const successRate = total.count > 0 ? ((success.count / total.count) * 100).toFixed(1) : '100';

    res.json({
      ...(stats || { total_volume: 0, transaction_count: 0, balance: 0 }),
      success_rate: successRate + '%'
    });
  });

  app.get("/api/merchant/stats/revenue/:userId", (req, res) => {
    const { userId } = req.params;
    const revenue = db.prepare(`
      SELECT 
        strftime('%Y-%m-%d', created_at) as date,
        SUM(amount) as revenue
      FROM transactions 
      WHERE user_id = ? AND status = 'success'
      GROUP BY date
      ORDER BY date DESC
      LIMIT 7
    `).all(userId);
    
    // Map to the format frontend expects
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const formatted = revenue.reverse().map((r: any) => ({
      name: days[new Date(r.date).getDay()],
      revenue: r.revenue
    }));
    
    res.json(formatted);
  });

  app.get("/api/merchant/transactions/:userId", (req, res) => {
    const { userId } = req.params;
    const transactions = db.prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC").all(userId);
    res.json(transactions);
  });

  app.get("/api/merchant/transactions/:id/timeline", (req, res) => {
    const { id } = req.params;
    const events = db.prepare("SELECT * FROM transaction_events WHERE transaction_id = ? ORDER BY created_at ASC").all(id);
    res.json(events);
  });

  app.post("/api/merchant/transactions/:id/refund", (req, res) => {
    const { id } = req.params;
    try {
      db.transaction(() => {
        const tx = db.prepare("SELECT * FROM transactions WHERE id = ?").get(id) as any;
        if (!tx) throw new Error("Transaction not found");
        if (tx.status !== 'success') throw new Error("Only successful transactions can be refunded");

        db.prepare("UPDATE transactions SET status = 'refunded', refunded_at = CURRENT_TIMESTAMP WHERE id = ?").run(id);
        
        // Deduct from wallet balance
        db.prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?").run(tx.amount, tx.user_id);

        const updatedUser = db.prepare("SELECT wallet_balance FROM users WHERE id = ?").get(tx.user_id) as any;
        db.prepare("INSERT INTO balance_ledger (user_id, amount, type, category, description, reference_id, balance_after) VALUES (?, ?, ?, ?, ?, ?, ?)")
          .run(tx.user_id, tx.amount, 'debit', 'refund', `Refund for transaction ${tx.reference}`, id, updatedUser.wallet_balance);

        // Log event
        db.prepare("INSERT INTO transaction_events (transaction_id, event_type, description) VALUES (?, ?, ?)")
          .run(id, 'refund', 'Refund initiated by merchant');
      })();
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  // Admin API
  app.get("/api/admin/stats", (req, res) => {
    const stats = db.prepare(`
      SELECT 
        SUM(amount) as total_gtv,
        COUNT(*) as total_transactions,
        (SELECT COUNT(*) FROM users WHERE role = 'merchant' AND deleted_at IS NULL) as active_merchants,
        (SELECT COUNT(*) FROM users WHERE role = 'merchant' AND is_kyc_verified = 0 AND deleted_at IS NULL) as pending_kyc
      FROM transactions 
      WHERE status = 'success'
    `).get() as any;
    
    // Calculate success rate
    const total = db.prepare("SELECT COUNT(*) as count FROM transactions").get() as any;
    const success = db.prepare("SELECT COUNT(*) as count FROM transactions WHERE status = 'success'").get() as any;
    const successRate = total.count > 0 ? ((success.count / total.count) * 100).toFixed(1) : '100';

    res.json({
      total_gtv: stats.total_gtv || 0,
      active_merchants: stats.active_merchants || 0,
      success_rate: successRate + '%',
      pending_kyc: stats.pending_kyc || 0
    });
  });

  app.get("/api/admin/merchants", (req, res) => {
    try {
      const merchants = db.prepare("SELECT id, email, full_name, business_name, is_kyc_verified, is_suspended, wallet_balance, fee_percentage, fee_flat FROM users WHERE role = 'merchant' AND deleted_at IS NULL").all();
      res.json(merchants);
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.post("/api/admin/merchants/delete", (req, res) => {
    try {
      const { userId } = req.body;
      db.prepare("UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?").run(userId);
      res.json({ success: true });
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.post("/api/admin/merchants/permanent-delete", (req, res) => {
    try {
      const { userId } = req.body;
      db.transaction(() => {
        db.prepare("DELETE FROM transactions WHERE user_id = ?").run(userId);
        db.prepare("DELETE FROM payouts WHERE user_id = ?").run(userId);
        db.prepare("DELETE FROM support_tickets WHERE user_id = ?").run(userId);
        db.prepare("DELETE FROM virtual_accounts WHERE user_id = ?").run(userId);
        db.prepare("DELETE FROM users WHERE id = ?").run(userId);
      })();
      res.json({ success: true });
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.post("/api/admin/merchants/impersonate", (req, res) => {
    const { userId } = req.body;
    const user = db.prepare("SELECT id, email, role, business_name, is_kyc_verified, is_suspended FROM users WHERE id = ?").get(userId);
    res.json(user);
  });

  app.post("/api/admin/merchants/verify", (req, res) => {
    const { userId, status, notes } = req.body;
    db.transaction(() => {
      db.prepare("UPDATE users SET is_kyc_verified = ?, kyc_notes = ? WHERE id = ?").run(status ? 1 : 0, notes || null, userId);
      db.prepare("UPDATE merchant_kyc SET status = ?, reviewed_at = CURRENT_TIMESTAMP, admin_notes = ? WHERE user_id = ? AND status = 'pending'").run(status ? 'approved' : 'rejected', notes || null, userId);
    })();
    res.json({ success: true });
  });

  app.post("/api/admin/merchants/suspend", (req, res) => {
    const { userId, status } = req.body;
    db.prepare("UPDATE users SET is_suspended = ? WHERE id = ?").run(status ? 1 : 0, userId);
    res.json({ success: true });
  });

  app.post("/api/admin/merchants/fees", (req, res) => {
    const { userId, fee_percentage, fee_flat } = req.body;
    db.prepare("UPDATE users SET fee_percentage = ?, fee_flat = ? WHERE id = ?").run(fee_percentage, fee_flat, userId);
    res.json({ success: true });
  });

  app.post("/api/admin/merchants/payout-review", (req, res) => {
    const { userId, status } = req.body;
    db.prepare("UPDATE users SET require_payout_review = ? WHERE id = ?").run(status ? 1 : 0, userId);
    res.json({ success: true });
  });

  app.get("/api/admin/disputes", (req, res) => {
    const disputes = db.prepare(`
      SELECT d.*, t.amount, t.customer_email, u.business_name 
      FROM disputes d 
      JOIN transactions t ON d.transaction_ref = t.reference 
      JOIN users u ON t.user_id = u.id
      ORDER BY d.expires_at ASC
    `).all();
    res.json(disputes);
  });

  app.post("/api/admin/disputes/adjudicate", (req, res) => {
    const { disputeId, outcome } = req.body;
    db.prepare("UPDATE disputes SET outcome = ?, status = ? WHERE id = ?").run(outcome, outcome, disputeId);
    res.json({ success: true });
  });

  app.post("/api/admin/disputes/evidence", (req, res) => {
    const { disputeId, evidence } = req.body;
    db.prepare("UPDATE disputes SET evidence_path = ? WHERE id = ?").run(evidence, disputeId);
    res.json({ success: true });
  });

  app.get("/api/admin/virtual-accounts", (req, res) => {
    const accounts = db.prepare(`
      SELECT va.*, u.business_name 
      FROM virtual_accounts va 
      JOIN users u ON va.user_id = u.id
    `).all();
    res.json(accounts);
  });

  app.get("/api/admin/webhook-logs", (req, res) => {
    const logs = db.prepare("SELECT * FROM webhook_logs ORDER BY last_attempt_at DESC LIMIT 100").all();
    res.json(logs);
  });

  app.post("/api/admin/webhooks/retry", (req, res) => {
    const { logId } = req.body;
    const log = db.prepare("SELECT * FROM webhook_logs WHERE id = ?").get(logId) as any;
    if (log) {
      // Simulate retry
      db.prepare("UPDATE webhook_logs SET attempt_count = attempt_count + 1, last_attempt_at = CURRENT_TIMESTAMP WHERE id = ?").run(logId);
      res.json({ success: true });
    } else {
      res.status(404).json({ error: "Log not found" });
    }
  });

  app.get("/api/admin/staff/roles", (req, res) => {
    const roles = db.prepare("SELECT * FROM staff_roles").all();
    res.json(roles);
  });

  app.post("/api/admin/staff/roles", (req, res) => {
    const { name, permissions } = req.body;
    db.prepare("INSERT INTO staff_roles (name, permissions) VALUES (?, ?)").run(name, JSON.stringify(permissions));
    res.json({ success: true });
  });

  app.get("/api/admin/staff/users", (req, res) => {
    const staff = db.prepare(`
      SELECT u.id, u.email, u.full_name, r.name as role_name 
      FROM users u 
      LEFT JOIN staff_assignments sa ON u.id = sa.user_id 
      LEFT JOIN staff_roles r ON sa.role_id = r.id 
      WHERE u.role = 'staff'
    `).all();
    res.json(staff);
  });

  app.post("/api/admin/staff/users", (req, res) => {
    const { email, password, full_name, roleId } = req.body;
    const result = db.prepare("INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)")
      .run(email, password, full_name, 'staff');
    db.prepare("INSERT INTO staff_assignments (user_id, role_id) VALUES (?, ?)")
      .run(result.lastInsertRowid, roleId);
    res.json({ success: true });
  });

  app.get("/api/admin/payouts", (req, res) => {
    try {
      const payouts = db.prepare(`
        SELECT p.*, u.business_name 
        FROM payouts p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.request_date DESC
      `).all();
      res.json(payouts);
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.post("/api/admin/payouts/process", (req, res) => {
    const { payoutId, status, details } = req.body;
    try {
      db.transaction(() => {
        const payout = db.prepare("SELECT * FROM payouts WHERE id = ?").get(payoutId) as any;
        if (!payout) throw new Error("Payout not found");
        if (payout.status !== 'pending') throw new Error("Payout already processed");

        db.prepare("UPDATE payouts SET status = ?, status_details = ? WHERE id = ?").run(status, details || `Payout ${status}`, payoutId);
        
        if (status === 'declined') {
          // Refund the merchant's balance
          db.prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?").run(payout.amount, payout.user_id);
          
          const updatedUser = db.prepare("SELECT wallet_balance FROM users WHERE id = ?").get(payout.user_id) as any;
          db.prepare("INSERT INTO balance_ledger (user_id, amount, type, category, description, balance_after) VALUES (?, ?, ?, ?, ?, ?)")
            .run(payout.user_id, payout.amount, 'credit', 'refund', `Refund for declined payout request`, updatedUser.wallet_balance);
        }
      })();
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.get("/api/admin/stats/activity", (req, res) => {
    const activity = db.prepare(`
      SELECT 
        strftime('%Y-%m-%d', created_at) as date,
        SUM(amount) as volume,
        COUNT(*) as count
      FROM transactions 
      WHERE status = 'success'
      GROUP BY date
      ORDER BY date DESC
      LIMIT 7
    `).all();
    
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const formatted = activity.reverse().map((r: any) => ({
      name: days[new Date(r.date).getDay()],
      volume: r.volume,
      count: r.count
    }));
    
    res.json(formatted);
  });

  app.get("/api/admin/stats/realtime", (req, res) => {
    const recent = db.prepare(`
      SELECT t.id, t.customer_email as 'from', u.business_name as 'to', t.amount, t.status
      FROM transactions t
      JOIN users u ON t.user_id = u.id
      ORDER BY t.created_at DESC
      LIMIT 5
    `).all();
    res.json(recent);
  });

  app.get("/api/admin/config", (req, res) => {
    const config = db.prepare("SELECT * FROM platform_config").all();
    res.json(config);
  });

  app.post("/api/admin/config", (req, res) => {
    const { key, value } = req.body;
    db.prepare("INSERT OR REPLACE INTO platform_config (key, value) VALUES (?, ?)").run(key, value);
    res.json({ success: true });
  });

  app.get("/api/admin/tickets", (req, res) => {
    const tickets = db.prepare(`
      SELECT t.*, u.email as user_email 
      FROM support_tickets t 
      JOIN users u ON t.user_id = u.id 
      ORDER BY t.created_at DESC
    `).all();
    res.json(tickets);
  });

  // Merchant Expanded API
  app.get("/api/merchant/virtual-accounts/:userId", (req, res) => {
    const accounts = db.prepare("SELECT * FROM virtual_accounts WHERE user_id = ?").all(req.params.userId);
    res.json(accounts);
  });

  app.post("/api/merchant/virtual-accounts", (req, res) => {
    const { userId } = req.body;
    const accNo = Math.floor(1000000000 + Math.random() * 9000000000).toString();
    db.prepare("INSERT INTO virtual_accounts (user_id, account_number, bank_name, account_name) VALUES (?, ?, ?, ?)")
      .run(userId, accNo, 'Payhub Bank', 'Merchant Account');
    res.json({ success: true });
  });

  app.get("/api/merchant/invoices/:userId", (req, res) => {
    const invoices = db.prepare("SELECT * FROM invoices WHERE user_id = ?").all(req.params.userId);
    res.json(invoices);
  });

  app.get("/api/merchant/subscriptions/:userId", (req, res) => {
    const subs = db.prepare("SELECT * FROM subscriptions WHERE user_id = ?").all(req.params.userId);
    res.json(subs);
  });

  app.get("/api/merchant/disputes/:userId", (req, res) => {
    const disputes = db.prepare(`
      SELECT d.* FROM disputes d 
      JOIN transactions t ON d.transaction_ref = t.reference 
      WHERE t.user_id = ?
    `).all(req.params.userId);
    res.json(disputes);
  });

  app.get("/api/merchant/tickets/:userId", (req, res) => {
    const tickets = db.prepare("SELECT * FROM support_tickets WHERE user_id = ?").all(req.params.userId);
    res.json(tickets);
  });

  app.post("/api/merchant/tickets", (req, res) => {
    const { userId, subject, message, priority } = req.body;
    db.prepare("INSERT INTO support_tickets (user_id, subject, message, priority) VALUES (?, ?, ?, ?)")
      .run(userId, subject, message, priority);
    res.json({ success: true });
  });

  app.get("/api/merchant/customers/:userId", (req, res) => {
    const customers = db.prepare("SELECT * FROM customers WHERE user_id = ?").all(req.params.userId);
    res.json(customers);
  });

  app.post("/api/merchant/payouts", (req, res) => {
    const { userId, amount } = req.body;
    try {
      db.transaction(() => {
        const user = db.prepare("SELECT * FROM users WHERE id = ?").get(userId) as any;
        if (!user) throw new Error("User not found");
        if (!user.settlement_bank || !user.settlement_account_number) {
          throw new Error("Please set up your settlement bank details in settings first.");
        }
        if (user.wallet_balance < amount) throw new Error("Insufficient balance");

        const globalReview = db.prepare("SELECT value FROM platform_config WHERE key = 'global_payout_review'").get() as any;
        const payoutFeeConfig = db.prepare("SELECT value FROM platform_config WHERE key = 'payout_fee'").get() as any;
        const payoutFee = parseFloat(payoutFeeConfig?.value || '0');
        const netAmount = amount - payoutFee;

        const needsReview = globalReview?.value === '1' || user.require_payout_review === 1;

        const status = needsReview ? 'pending' : 'processed';
        const details = needsReview ? 'Awaiting administrative review' : 'Processed automatically';

        // Deduct balance immediately
        db.prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?").run(amount, userId);

        const updatedUser = db.prepare("SELECT wallet_balance FROM users WHERE id = ?").get(userId) as any;
        db.prepare("INSERT INTO balance_ledger (user_id, amount, type, category, description, balance_after) VALUES (?, ?, ?, ?, ?, ?)")
          .run(userId, amount, 'debit', 'payout', `Payout request of ${amount}`, updatedUser.wallet_balance);

        db.prepare("INSERT INTO payouts (user_id, amount, fee_amount, net_amount, bank_name, account_number, status, status_details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
          .run(userId, amount, payoutFee, netAmount, user.settlement_bank, user.settlement_account_number, status, details);
      })();
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.get("/api/merchant/ledger/:userId", (req, res) => {
    const ledger = db.prepare("SELECT * FROM balance_ledger WHERE user_id = ? ORDER BY created_at DESC").all(req.params.userId);
    res.json(ledger);
  });

  app.get("/api/merchant/api-keys/:userId", (req, res) => {
    const { userId } = req.params;
    let keys = db.prepare("SELECT public_key, secret_key FROM users WHERE id = ?").get(userId) as any;
    
    // If no keys exist, generate them (first time access)
    if (!keys || !keys.public_key) {
      const public_key = `pk_live_${Math.random().toString(36).substring(2, 15)}`;
      const secret_key = `sk_live_${Math.random().toString(36).substring(2, 15)}`;
      db.prepare("UPDATE users SET public_key = ?, secret_key = ? WHERE id = ?").run(public_key, secret_key, userId);
      keys = { public_key, secret_key };
    }
    
    res.json(keys);
  });

  app.post("/api/merchant/api-keys/regenerate", (req, res) => {
    const { userId } = req.body;
    try {
      const public_key = `pk_live_${Math.random().toString(36).substring(2, 15)}`;
      const secret_key = `sk_live_${Math.random().toString(36).substring(2, 15)}`;
      
      db.prepare("UPDATE users SET public_key = ?, secret_key = ? WHERE id = ?").run(public_key, secret_key, userId);
      
      res.json({ public_key, secret_key });
    } catch (e: any) {
      res.status(500).json({ error: e.message });
    }
  });

  app.get("/api/admin/health/stats", (req, res) => {
    res.json({
      uptime: process.uptime(),
      memory: process.memoryUsage(),
      db_size: "1.2MB",
      active_connections: 42,
      api_latency: "45ms"
    });
  });

  // CMS API
  app.get("/api/blog", (req, res) => {
    const posts = db.prepare("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC").all();
    res.json(posts);
  });

  const NGN_BANKS = [
    { name: "Access Bank", code: "044" },
    { name: "Citibank Nigeria", code: "023" },
    { name: "Ecobank Nigeria", code: "050" },
    { name: "Fidelity Bank", code: "070" },
    { name: "First Bank of Nigeria", code: "011" },
    { name: "First City Monument Bank", code: "214" },
    { name: "Guaranty Trust Bank", code: "058" },
    { name: "Heritage Bank", code: "030" },
    { name: "Keystone Bank", code: "082" },
    { name: "Polaris Bank", code: "076" },
    { name: "Providus Bank", code: "101" },
    { name: "Stanbic IBTC Bank", code: "221" },
    { name: "Standard Chartered Bank", code: "068" },
    { name: "Sterling Bank", code: "232" },
    { name: "Suntrust Bank", code: "100" },
    { name: "Union Bank of Nigeria", code: "032" },
    { name: "United Bank for Africa", code: "033" },
    { name: "Unity Bank", code: "215" },
    { name: "Wema Bank", code: "035" },
    { name: "Zenith Bank", code: "057" },
    { name: "Kuda Bank", code: "50211" },
    { name: "Opay", code: "999992" },
    { name: "Palmpay", code: "999991" }
  ];

  app.get("/api/banks", (req, res) => {
    res.json(NGN_BANKS);
  });

  app.get("/api/merchant/profile/:userId", (req, res) => {
    const user = db.prepare("SELECT email, full_name, business_name, phone_number, webhook_url, is_kyc_verified, settlement_bank, settlement_account_number, settlement_account_name, business_type FROM users WHERE id = ?").get(req.params.userId);
    res.json(user);
  });

  app.post("/api/merchant/settlement-bank", (req, res) => {
    const { userId, bank_name, account_number, account_name } = req.body;
    try {
      db.prepare("UPDATE users SET settlement_bank = ?, settlement_account_number = ?, settlement_account_name = ? WHERE id = ?")
        .run(bank_name, account_number, account_name, userId);
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.get("/api/merchant/kyc/:userId", (req, res) => {
    const kyc = db.prepare("SELECT * FROM merchant_kyc WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1").get(req.params.userId);
    res.json(kyc || null);
  });

  app.post("/api/merchant/kyc", (req, res) => {
    const { userId, business_type, ...data } = req.body;
    try {
      const columns = ['user_id', 'business_type', ...Object.keys(data)];
      const placeholders = columns.map(() => '?').join(', ');
      const values = [userId, business_type, ...Object.values(data)];
      
      db.prepare(`INSERT INTO merchant_kyc (${columns.join(', ')}) VALUES (${placeholders})`).run(...values);
      
      // Update user status to pending if not already verified and update business_type
      db.prepare("UPDATE users SET is_kyc_verified = 2, business_type = ? WHERE id = ? AND is_kyc_verified != 1").run(business_type, userId);
      
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.post("/api/merchant/kyc/upload", (req, res) => {
    // In a real app, use multer to save the file.
    // Here we simulate by returning a fake path.
    const { type, userId } = req.body;
    const timestamp = Date.now();
    const fileName = `merchant_${userId}_${type}_${timestamp}.pdf`;
    const filePath = `/uploads/kyc/${fileName}`;
    
    res.json({ path: filePath });
  });

  app.post("/api/merchant/profile", (req, res) => {
    const { userId, email, phone_number, webhook_url } = req.body;
    try {
      db.prepare("UPDATE users SET email = ?, phone_number = ?, webhook_url = ? WHERE id = ?")
        .run(email, phone_number, webhook_url, userId);
      res.json({ success: true });
    } catch (e: any) {
      res.status(400).json({ error: e.message });
    }
  });

  app.post("/api/blog", (req, res) => {
    const { title, slug, content, author_id, excerpt, meta_title, meta_description } = req.body;
    db.prepare(`
      INSERT INTO blog_posts (title, slug, content, author_id, excerpt, meta_title, meta_description) 
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `).run(title, slug, content, author_id, excerpt, meta_title, meta_description);
    res.json({ success: true });
  });

  // Mobile App API Folder (Simulated structure)
  app.get("/api/app/v1/status", (req, res) => {
    res.json({ app: "Payhub Mobile", version: "1.0.0", status: "online" });
  });

  // Vite middleware for development
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    app.use(express.static(path.resolve(__dirname, "dist")));
    app.get("*", (req, res) => {
      res.sendFile(path.resolve(__dirname, "dist", "index.html"));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
