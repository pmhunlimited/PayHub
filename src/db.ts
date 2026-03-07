import Database from 'better-sqlite3';
import path from 'path';

const db = new Database('payhub.db');

// Enable foreign keys
db.pragma('foreign_keys = ON');

// Initialize tables
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT,
    business_name TEXT,
    role TEXT CHECK(role IN ('admin', 'merchant', 'support', 'finance', 'staff')) DEFAULT 'merchant',
    is_kyc_verified INTEGER DEFAULT 0,
    kyc_notes TEXT,
    is_suspended INTEGER DEFAULT 0,
    is_test_mode INTEGER DEFAULT 1,
    wallet_balance DECIMAL(15, 2) DEFAULT 0.00,
    fee_percentage DECIMAL(5, 2), -- Custom merchant fee
    fee_flat DECIMAL(10, 2),      -- Custom merchant fee
    webhook_url TEXT,
    phone_number TEXT,
    settlement_bank TEXT,
    settlement_account_number TEXT,
    settlement_account_name TEXT,
    require_payout_review INTEGER DEFAULT 0,
    business_type TEXT DEFAULT 'Starter',
    public_key TEXT UNIQUE,
    secret_key TEXT UNIQUE,
    deleted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS balance_ledger (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    type TEXT CHECK(type IN ('credit', 'debit')) NOT NULL,
    category TEXT NOT NULL, -- 'transaction', 'payout', 'refund', 'fee'
    description TEXT,
    reference_id INTEGER, -- ID of transaction or payout
    balance_after DECIMAL(15, 2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS staff_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    permissions TEXT NOT NULL -- JSON string of permissions
  );

  CREATE TABLE IF NOT EXISTS staff_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES staff_roles(id)
  );

  CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    reference TEXT UNIQUE NOT NULL,
    provider_ref TEXT,
    amount DECIMAL(15, 2) NOT NULL,
    fee_amount DECIMAL(15, 2) NOT NULL,
    settled_amount DECIMAL(15, 2) NOT NULL,
    customer_email TEXT,
    status TEXT CHECK(status IN ('pending', 'success', 'failed', 'reversed', 'refunded')) DEFAULT 'pending',
    type TEXT CHECK(type IN ('collection', 'transfer', 'subscription')) DEFAULT 'collection',
    refunded_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS transaction_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
  );

  CREATE TABLE IF NOT EXISTS payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    fee_amount DECIMAL(15, 2) DEFAULT 0,
    net_amount DECIMAL(15, 2) DEFAULT 0,
    bank_name TEXT,
    account_number TEXT,
    status TEXT CHECK(status IN ('pending', 'approved', 'declined', 'processed')) DEFAULT 'pending',
    status_details TEXT,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    subject TEXT,
    status TEXT CHECK(status IN ('open', 'answered', 'closed')) DEFAULT 'open',
    priority TEXT CHECK(priority IN ('low', 'medium', 'high')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS ticket_replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    message TEXT,
    attachment_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS disputes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_ref TEXT NOT NULL,
    reason TEXT,
    evidence_path TEXT, -- Paystack evidence
    merchant_evidence TEXT,
    merchant_response_at DATETIME,
    status TEXT CHECK(status IN ('open', 'won', 'lost', 'pending_merchant')) DEFAULT 'open',
    outcome TEXT CHECK(outcome IN ('won', 'lost', 'pending')) DEFAULT 'pending',
    expires_at DATETIME,
    FOREIGN KEY (transaction_ref) REFERENCES transactions(reference)
  );

  CREATE TABLE IF NOT EXISTS cms_content (
    key_name TEXT PRIMARY KEY,
    content_value TEXT
  );

  CREATE TABLE IF NOT EXISTS cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE,
    title TEXT,
    body_content TEXT,
    is_published INTEGER DEFAULT 1
  );

  CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    excerpt TEXT,
    content TEXT NOT NULL,
    author_id INTEGER NOT NULL,
    meta_title TEXT,
    meta_description TEXT,
    is_published INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS virtual_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    account_number TEXT UNIQUE,
    bank_name TEXT,
    account_name TEXT,
    provider TEXT DEFAULT 'paystack',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    customer_name TEXT,
    customer_email TEXT,
    amount DECIMAL(15, 2) NOT NULL,
    due_date DATE,
    status TEXT CHECK(status IN ('draft', 'sent', 'paid', 'overdue')) DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_name TEXT,
    amount DECIMAL(15, 2) NOT NULL,
    interval TEXT CHECK(interval IN ('daily', 'weekly', 'monthly', 'annually')),
    status TEXT CHECK(status IN ('active', 'cancelled', 'expired')) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS merchant_kyc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    business_type TEXT CHECK(business_type IN ('starter', 'registered', 'business_name', 'special')) NOT NULL,
    
    -- Starter
    bvn TEXT,
    id_type TEXT,
    id_path TEXT,
    residential_address TEXT,
    
    -- Registered / LLC
    rc_number TEXT,
    tin TEXT,
    cac_cert_path TEXT,
    cac_form_path TEXT,
    memart_path TEXT,
    business_address_proof_path TEXT,
    
    -- Business Name
    bn_number TEXT,
    bn_cert_path TEXT,
    bn_form_path TEXT,
    
    -- Special
    ngo_form_path TEXT,
    ngo_constitution_path TEXT,
    gov_auth_letter_path TEXT,
    gov_gazette_path TEXT,
    
    status TEXT CHECK(status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
    admin_notes TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS platform_config (
    key TEXT PRIMARY KEY,
    value TEXT
  );

  CREATE TABLE IF NOT EXISTS webhook_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_ref TEXT NOT NULL,
    url TEXT NOT NULL,
    payload TEXT NOT NULL,
    response_code INTEGER,
    response_body TEXT,
    status TEXT CHECK(status IN ('success', 'failed')) DEFAULT 'failed',
    attempt_count INTEGER DEFAULT 1,
    last_attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_ref) REFERENCES transactions(reference)
  );
`);

// Migration: Add missing columns if they don't exist
const tableInfo = db.prepare("PRAGMA table_info(users)").all() as any[];
const columns = tableInfo.map(c => c.name);

if (!columns.includes('is_suspended')) {
  db.exec("ALTER TABLE users ADD COLUMN is_suspended INTEGER DEFAULT 0");
}
if (!columns.includes('kyc_notes')) {
  db.exec("ALTER TABLE users ADD COLUMN kyc_notes TEXT");
}
if (!columns.includes('fee_percentage')) {
  db.exec("ALTER TABLE users ADD COLUMN fee_percentage DECIMAL(5, 2)");
}
if (!columns.includes('fee_flat')) {
  db.exec("ALTER TABLE users ADD COLUMN fee_flat DECIMAL(10, 2)");
}
if (!columns.includes('deleted_at')) {
  db.exec("ALTER TABLE users ADD COLUMN deleted_at DATETIME");
}
if (!columns.includes('webhook_url')) {
  db.exec("ALTER TABLE users ADD COLUMN webhook_url TEXT");
}
if (!columns.includes('phone_number')) {
  db.exec("ALTER TABLE users ADD COLUMN phone_number TEXT");
}
if (!columns.includes('settlement_bank')) {
  db.exec("ALTER TABLE users ADD COLUMN settlement_bank TEXT");
}
if (!columns.includes('settlement_account_number')) {
  db.exec("ALTER TABLE users ADD COLUMN settlement_account_number TEXT");
}
if (!columns.includes('settlement_account_name')) {
  db.exec("ALTER TABLE users ADD COLUMN settlement_account_name TEXT");
}
if (!columns.includes('require_payout_review')) {
  db.exec("ALTER TABLE users ADD COLUMN require_payout_review INTEGER DEFAULT 0");
}
if (!columns.includes('business_type')) {
  db.exec("ALTER TABLE users ADD COLUMN business_type TEXT DEFAULT 'Starter'");
}
if (!columns.includes('public_key')) {
  db.exec("ALTER TABLE users ADD COLUMN public_key TEXT UNIQUE");
}
if (!columns.includes('secret_key')) {
  db.exec("ALTER TABLE users ADD COLUMN secret_key TEXT UNIQUE");
}

// Ensure other tables exist (already handled by CREATE TABLE IF NOT EXISTS)
// But some might need migrations too if they were added later
const disputeInfo = db.prepare("PRAGMA table_info(disputes)").all() as any[];
const disputeColumns = disputeInfo.map(c => c.name);
if (!disputeColumns.includes('evidence_path')) {
  db.exec("ALTER TABLE disputes ADD COLUMN evidence_path TEXT");
}

const payoutInfo = db.prepare("PRAGMA table_info(payouts)").all() as any[];
const payoutColumns = payoutInfo.map(c => c.name);
if (!payoutColumns.includes('bank_name')) {
  db.exec("ALTER TABLE payouts ADD COLUMN bank_name TEXT");
}
if (!payoutColumns.includes('account_number')) {
  db.exec("ALTER TABLE payouts ADD COLUMN account_number TEXT");
}
if (!payoutColumns.includes('status_details')) {
  db.exec("ALTER TABLE payouts ADD COLUMN status_details TEXT");
}
if (!payoutColumns.includes('fee_amount')) {
  db.exec("ALTER TABLE payouts ADD COLUMN fee_amount DECIMAL(15, 2) DEFAULT 0");
}
if (!payoutColumns.includes('net_amount')) {
  db.exec("ALTER TABLE payouts ADD COLUMN net_amount DECIMAL(15, 2) DEFAULT 0");
}

const txInfo = db.prepare("PRAGMA table_info(transactions)").all() as any[];
const txColumns = txInfo.map(c => c.name);
if (!txColumns.includes('refunded_at')) {
  db.exec("ALTER TABLE transactions ADD COLUMN refunded_at DATETIME");
}

// Ensure transaction_events table exists
db.exec(`
  CREATE TABLE IF NOT EXISTS transaction_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
  );
`);

// Seed data
if (process.env.SEED_DB === 'true') {
  const userCount = db.prepare("SELECT COUNT(*) as count FROM users").get() as any;
  if (userCount.count === 0) {
  const insertUser = db.prepare("INSERT INTO users (email, password_hash, full_name, business_name, role, is_kyc_verified, wallet_balance) VALUES (?, ?, ?, ?, ?, ?, ?)");
  insertUser.run('admin@payhub.com', 'admin123', 'Super Admin', 'Payhub HQ', 'admin', 1, 0);
  const merchant = insertUser.run('merchant@example.com', 'merchant123', 'John Merchant', 'Acme Store', 'merchant', 1, 50000);
  
  const insertTx = db.prepare("INSERT INTO transactions (user_id, reference, amount, fee_amount, settled_amount, customer_email, status, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  const tx1 = insertTx.run(merchant.lastInsertRowid, 'TX-' + Math.random().toString(36).substr(2, 9).toUpperCase(), 10000, 150, 9850, 'customer1@gmail.com', 'success', 'collection');
  const tx2 = insertTx.run(merchant.lastInsertRowid, 'TX-' + Math.random().toString(36).substr(2, 9).toUpperCase(), 5000, 75, 4925, 'customer2@gmail.com', 'success', 'collection');
  const tx3 = insertTx.run(merchant.lastInsertRowid, 'TX-' + Math.random().toString(36).substr(2, 9).toUpperCase(), 2000, 30, 1970, 'customer3@gmail.com', 'pending', 'collection');

  // Seed Ledger
  const insertLedger = db.prepare("INSERT INTO balance_ledger (user_id, amount, type, category, description, reference_id, balance_after) VALUES (?, ?, ?, ?, ?, ?, ?)");
  insertLedger.run(merchant.lastInsertRowid, 10000, 'credit', 'transaction', 'Payment received for TX-1', tx1.lastInsertRowid, 10000);
  insertLedger.run(merchant.lastInsertRowid, 5000, 'credit', 'transaction', 'Payment received for TX-2', tx2.lastInsertRowid, 15000);
  insertLedger.run(merchant.lastInsertRowid, 35000, 'credit', 'refund', 'Initial wallet credit', null, 50000);

  // Seed events for tx1
  const insertEvent = db.prepare("INSERT INTO transaction_events (transaction_id, event_type, description) VALUES (?, ?, ?)");
  insertEvent.run(tx1.lastInsertRowid, 'initiation', 'Payment initiated via Card');
  insertEvent.run(tx1.lastInsertRowid, 'authorization', 'Bank authorization successful');
  insertEvent.run(tx1.lastInsertRowid, 'completion', 'Transaction completed successfully');

  // Seed events for tx2
  insertEvent.run(tx2.lastInsertRowid, 'initiation', 'Payment initiated via Bank Transfer');
  insertEvent.run(tx2.lastInsertRowid, 'completion', 'Transaction completed successfully');

  // Seed events for tx3
  insertEvent.run(tx3.lastInsertRowid, 'initiation', 'Payment initiated via USSD');

  // Seed platform config
  const insertConfig = db.prepare("INSERT INTO platform_config (key, value) VALUES (?, ?)");
  insertConfig.run('paystack_secret_key', 'sk_test_placeholder');
  insertConfig.run('transaction_fee_percent', '1.5');
  insertConfig.run('transaction_fee_flat', '100');
  insertConfig.run('payout_fee', '50');
  insertConfig.run('global_payout_review', '1');
  insertConfig.run('smtp_host', 'smtp.example.com');
  insertConfig.run('smtp_port', '587');
  insertConfig.run('smtp_user', 'user@example.com');
  insertConfig.run('smtp_pass', 'password');
  insertConfig.run('smtp_from', 'noreply@payhub.com');

  // Seed Staff Roles
  const insertRole = db.prepare("INSERT INTO staff_roles (name, permissions) VALUES (?, ?)");
  insertRole.run('Support Agent', JSON.stringify(['view_tickets', 'reply_tickets', 'view_merchants']));
  insertRole.run('Finance Officer', JSON.stringify(['view_settlements', 'process_settlements', 'view_transactions']));
  
  // Seed a staff user
  const staffUser = db.prepare("INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)").run('support@payhub.com', 'support123', 'Support Sarah', 'staff');
  db.prepare("INSERT INTO staff_assignments (user_id, role_id) VALUES (?, ?)").run(staffUser.lastInsertRowid, 1);

  // Seed some disputes
  const insertDispute = db.prepare("INSERT INTO disputes (transaction_ref, reason, status) VALUES (?, ?, ?)");
  insertDispute.run('TX-ABC123XYZ', 'Product not received', 'open');
  }
}

export default db;
