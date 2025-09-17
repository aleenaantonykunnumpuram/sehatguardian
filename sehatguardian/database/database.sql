-- 1. Users table (stores login for admin, doctor, patient)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Doctor Profile
CREATE TABLE IF NOT EXISTS doctor_profile (
    doctor_id INT PRIMARY KEY,
    specialization VARCHAR(100),
    about TEXT,
    age INT,
    id_proof VARCHAR(255),
    profile_pic VARCHAR(255),
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- 3. Patient Profile
CREATE TABLE IF NOT EXISTS patient_profile (
    patient_id INT PRIMARY KEY,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    address TEXT,
    weight DECIMAL(5,2),         -- in kg
    height DECIMAL(5,2),         -- in cm
    blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
    allergies TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(15),
    emergency_contact_relation VARCHAR(50),
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- 4. Payments

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  appointment_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending', 'processing', 'approved', 'rejected') DEFAULT 'pending',
  billing_name VARCHAR(120),
  billing_card VARCHAR(20),
  billing_expiry VARCHAR(7),
  billing_address VARCHAR(255),
  payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  rejection_reason VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_payments_patient FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);


-- 5. Appointments
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 6. Health Log
CREATE TABLE IF NOT EXISTS health_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    log_date DATE NOT NULL,
    bp VARCHAR(20),
    sugar VARCHAR(20),
    water_intake DECIMAL(5,2),
    sleep_hours DECIMAL(4,2),
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);
-- 7. Medicine Schedule
CREATE TABLE IF NOT EXISTS medicine_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) DEFAULT NULL,
    time TIME NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    status ENUM('Pending', 'Taken') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- 8. Emergency Alerts
CREATE TABLE IF NOT EXISTS emergency_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    alert_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location VARCHAR(255),
    message TEXT,
    status ENUM('Sent','Acknowledged') DEFAULT 'Sent',
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 9. Feedback / Complaints
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    message TEXT NOT NULL,
    reply TEXT,
    status ENUM('Pending','Replied') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id) ON DELETE CASCADE
);
