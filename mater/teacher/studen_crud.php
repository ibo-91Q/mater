<?php
/**
 * Student CRUD Operations
 * 
 * This file contains functions for handling Create, Read, Update, and Delete
 * operations for student accounts in the DebateSkills platform.
 */

// Database connection
function get_db_connection() {
    $host = "localhost";
    $dbname = "debateskills";
    $username = "root";
    $password = "";
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

/**
 * Create a new student account
 * 
 * @param array $user_data User data (email, password, first_name, last_name)
 * @param array $student_data Student specific data (grade_level, school, etc.)
 * @return int|bool Returns the new student_id on success, false on failure
 */
function create_student($user_data, $student_data) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction to ensure both user and student records are created
        $conn->beginTransaction();
        
        // Create user record first
        $user_sql = "INSERT INTO users (email, password_hash, first_name, last_name, account_type, account_status) 
                    VALUES (:email, :password_hash, :first_name, :last_name, 'student', 'active')";
        
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bindParam(':email', $user_data['email']);
        // Hash the password for security
        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
        $user_stmt->bindParam(':password_hash', $password_hash);
        $user_stmt->bindParam(':first_name', $user_data['first_name']);
        $user_stmt->bindParam(':last_name', $user_data['last_name']);
        $user_stmt->execute();
        
        // Get the new user_id
        $user_id = $conn->lastInsertId();
        
        // Create student record
        $student_sql = "INSERT INTO students (
                        user_id, grade_level, school, parent_name, parent_email, 
                        debate_experience, joined_date, subscription_level
                    ) VALUES (
                        :user_id, :grade_level, :school, :parent_name, :parent_email,
                        :debate_experience, NOW(), :subscription_level
                    )";
        
        $student_stmt = $conn->prepare($student_sql);
        $student_stmt->bindParam(':user_id', $user_id);
        $student_stmt->bindParam(':grade_level', $student_data['grade_level']);
        $student_stmt->bindParam(':school', $student_data['school']);
        $student_stmt->bindParam(':parent_name', $student_data['parent_name']);
        $student_stmt->bindParam(':parent_email', $student_data['parent_email']);
        $student_stmt->bindParam(':debate_experience', $student_data['debate_experience']);
        $student_stmt->bindParam(':subscription_level', $student_data['subscription_level']);
        $student_stmt->execute();
        
        // Get the new student_id
        $student_id = $conn->lastInsertId();
        
        // Commit the transaction
        $conn->commit();
        
        return $student_id;
    } catch(PDOException $e) {
        // Roll back the transaction if an error occurred
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error creating student: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all students
 * 
 * @param int $limit Maximum number of records to retrieve
 * @param int $offset Offset for pagination
 * @param string $order_by Field to order by
 * @param string $order_dir Direction of ordering (ASC or DESC)
 * @return array Returns an array of student records
 */
function get_all_students($limit = 25, $offset = 0, $order_by = 'last_name', $order_dir = 'ASC') {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.student_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    s.grade_level,
                    s.school,
                    s.debate_experience,
                    s.subscription_level,
                    s.joined_date
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    u.account_status = 'active'
                ORDER BY 
                    u.$order_by $order_dir
                LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error retrieving students: " . $e->getMessage());
        return [];
    }
}

/**
 * Get student by ID
 * 
 * @param int $student_id The student ID to lookup
 * @return array|bool Returns the student data on success, false on failure
 */
function get_student_by_id($student_id) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.*,
                    u.user_id,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.account_status,
                    u.date_registered,
                    u.last_login
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    s.student_id = :student_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error retrieving student: " . $e->getMessage());
        return false;
    }
}

/**
 * Get student by user ID
 * 
 * @param int $user_id The user ID to lookup
 * @return array|bool Returns the student data on success, false on failure
 */
function get_student_by_user_id($user_id) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.*,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.account_status,
                    u.date_registered,
                    u.last_login
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    s.user_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error retrieving student: " . $e->getMessage());
        return false;
    }
}

/**
 * Update student information
 * 
 * @param int $student_id The student ID to update
 * @param array $user_data User data to update (email, first_name, last_name)
 * @param array $student_data Student specific data to update
 * @return bool Returns true on success, false on failure
 */
function update_student($student_id, $user_data = [], $student_data = []) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Get the user_id for this student
        $get_user_id_sql = "SELECT user_id FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Student not found
            return false;
        }
        
        $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Update user data if provided
        if (!empty($user_data)) {
            $user_update_fields = [];
            $user_params = [];
            
            // Build dynamic update query based on provided fields
            foreach ($user_data as $key => $value) {
                if (in_array($key, ['email', 'first_name', 'last_name', 'account_status'])) {
                    $user_update_fields[] = "$key = :$key";
                    $user_params[":$key"] = $value;
                }
            }
            
            if (!empty($user_update_fields)) {
                $user_sql = "UPDATE users SET " . implode(", ", $user_update_fields) . " WHERE user_id = :user_id";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                foreach ($user_params as $param => $value) {
                    $user_stmt->bindValue($param, $value);
                }
                
                $user_stmt->execute();
            }
        }
        
        // Update student data if provided
        if (!empty($student_data)) {
            $student_update_fields = [];
            $student_params = [];
            
            // Build dynamic update query based on provided fields
            foreach ($student_data as $key => $value) {
                if (in_array($key, ['grade_level', 'school', 'parent_name', 'parent_email', 'debate_experience', 'subscription_level'])) {
                    $student_update_fields[] = "$key = :$key";
                    $student_params[":$key"] = $value;
                }
            }
            
            if (!empty($student_update_fields)) {
                $student_sql = "UPDATE students SET " . implode(", ", $student_update_fields) . " WHERE student_id = :student_id";
                $student_stmt = $conn->prepare($student_sql);
                $student_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                
                foreach ($student_params as $param => $value) {
                    $student_stmt->bindValue($param, $value);
                }
                
                $student_stmt->execute();
            }
        }
        
        // Update password if provided
        if (isset($user_data['password']) && !empty($user_data['password'])) {
            $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $pwd_sql = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
            $pwd_stmt = $conn->prepare($pwd_sql);
            $pwd_stmt->bindParam(':password_hash', $password_hash);
            $pwd_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $pwd_stmt->execute();
        }
        
        // Commit the transaction
        $conn->commit();
        
        return true;
    } catch(PDOException $e) {
        // Roll back the transaction if an error occurred
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error updating student: " . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate a student account
 * 
 * @param int $student_id The student ID to deactivate
 * @return bool Returns true on success, false on failure
 */
function deactivate_student($student_id) {
    try {
        $conn = get_db_connection();
        
        // Get the user_id for this student
        $get_user_id_sql = "SELECT user_id FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Student not found
            return false;
        }
        
        $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Set account status to inactive
        $sql = "UPDATE users SET account_status = 'inactive' WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return true;
    } catch(PDOException $e) {
        error_log("Error deactivating student: " . $e->getMessage());
        return false;
    }
}

/**
 * Activate a student account
 * 
 * @param int $student_id The student ID to activate
 * @return bool Returns true on success, false on failure
 */
function activate_student($student_id) {
    try {
        $conn = get_db_connection();
        
        // Get the user_id for this student
        $get_user_id_sql = "SELECT user_id FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Student not found
            return false;
        }
        
        $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Set account status to active
        $sql = "UPDATE users SET account_status = 'active' WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return true;
    } catch(PDOException $e) {
        error_log("Error activating student: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a student account (completely removes both student and user records)
 * 
 * @param int $student_id The student ID to delete
 * @return bool Returns true on success, false on failure
 */
function delete_student($student_id) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Get the user_id for this student
        $get_user_id_sql = "SELECT user_id FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Student not found
            return false;
        }
        
        $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Delete student record
        $delete_student_sql = "DELETE FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($delete_student_sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Delete user record
        $delete_user_sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $conn->prepare($delete_user_sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        return true;
    } catch(PDOException $e) {
        // Roll back the transaction if an error occurred
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error deleting student: " . $e->getMessage());
        return false;
    }
}

/**
 * Search for students based on various criteria
 * 
 * @param array $search_params Search parameters (name, school, grade_level, etc.)
 * @param int $limit Maximum number of records to retrieve
 * @param int $offset Offset for pagination
 * @return array Returns an array of matching student records
 */
function search_students($search_params, $limit = 25, $offset = 0) {
    try {
        $conn = get_db_connection();
        
        $conditions = [];
        $params = [];
        
        // Build search conditions based on provided parameters
        if (isset($search_params['name']) && !empty($search_params['name'])) {
            $conditions[] = "(u.first_name LIKE :name OR u.last_name LIKE :name)";
            $params[':name'] = '%' . $search_params['name'] . '%';
        }
        
        if (isset($search_params['email']) && !empty($search_params['email'])) {
            $conditions[] = "u.email LIKE :email";
            $params[':email'] = '%' . $search_params['email'] . '%';
        }
        
        if (isset($search_params['school']) && !empty($search_params['school'])) {
            $conditions[] = "s.school LIKE :school";
            $params[':school'] = '%' . $search_params['school'] . '%';
        }
        
        if (isset($search_params['grade_level']) && !empty($search_params['grade_level'])) {
            $conditions[] = "s.grade_level = :grade_level";
            $params[':grade_level'] = $search_params['grade_level'];
        }
        
        if (isset($search_params['subscription_level']) && !empty($search_params['subscription_level'])) {
            $conditions[] = "s.subscription_level = :subscription_level";
            $params[':subscription_level'] = $search_params['subscription_level'];
        }
        
        if (isset($search_params['debate_experience']) && $search_params['debate_experience'] !== '') {
            $conditions[] = "s.debate_experience = :debate_experience";
            $params[':debate_experience'] = $search_params['debate_experience'];
        }
        
        // Always only show active accounts
        $conditions[] = "u.account_status = 'active'";
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "SELECT 
                    s.student_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    s.grade_level,
                    s.school,
                    s.debate_experience,
                    s.subscription_level,
                    s.joined_date
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                $where_clause
                ORDER BY 
                    u.last_name ASC, u.first_name ASC
                LIMIT :offset, :limit";
        
        $stmt = $conn->prepare($sql);
        
        // Bind all search parameters
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error searching students: " . $e->getMessage());
        return [];
    }
}

/**
 * Count total students matching search criteria
 * 
 * @param array $search_params Search parameters (same as search_students)
 * @return int Returns the total count of matching records
 */
function count_students($search_params = []) {
    try {
        $conn = get_db_connection();
        
        $conditions = [];
        $params = [];
        
        // Build search conditions based on provided parameters
        if (isset($search_params['name']) && !empty($search_params['name'])) {
            $conditions[] = "(u.first_name LIKE :name OR u.last_name LIKE :name)";
            $params[':name'] = '%' . $search_params['name'] . '%';
        }
        
        if (isset($search_params['email']) && !empty($search_params['email'])) {
            $conditions[] = "u.email LIKE :email";
            $params[':email'] = '%' . $search_params['email'] . '%';
        }
        
        if (isset($search_params['school']) && !empty($search_params['school'])) {
            $conditions[] = "s.school LIKE :school";
            $params[':school'] = '%' . $search_params['school'] . '%';
        }
        
        if (isset($search_params['grade_level']) && !empty($search_params['grade_level'])) {
            $conditions[] = "s.grade_level = :grade_level";
            $params[':grade_level'] = $search_params['grade_level'];
        }
        
        if (isset($search_params['subscription_level']) && !empty($search_params['subscription_level'])) {
            $conditions[] = "s.subscription_level = :subscription_level";
            $params[':subscription_level'] = $search_params['subscription_level'];
        }
        
        if (isset($search_params['debate_experience']) && $search_params['debate_experience'] !== '') {
            $conditions[] = "s.debate_experience = :debate_experience";
            $params[':debate_experience'] = $search_params['debate_experience'];
        }
        
        // Always only count active accounts
        $conditions[] = "u.account_status = 'active'";
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "SELECT 
                    COUNT(*) as total
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                $where_clause";
        
        $stmt = $conn->prepare($sql);
        
        // Bind all search parameters
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        error_log("Error counting students: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get students by school
 * 
 * @param string $school The school name
 * @return array Returns an array of student records
 */
function get_students_by_school($school) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.student_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    s.grade_level,
                    s.debate_experience,
                    s.subscription_level
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    s.school LIKE :school
                    AND u.account_status = 'active'
                ORDER BY 
                    s.grade_level ASC, u.last_name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':school', '%' . $school . '%');
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting students by school: " . $e->getMessage());
        return [];
    }
}

/**
 * Get students by subscription level
 * 
 * @param string $subscription_level The subscription level
 * @return array Returns an array of student records
 */
function get_students_by_subscription($subscription_level) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.student_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    s.grade_level,
                    s.school,
                    s.debate_experience,
                    s.joined_date
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    s.subscription_level = :subscription_level
                    AND u.account_status = 'active'
                ORDER BY 
                    s.joined_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':subscription_level', $subscription_level);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting students by subscription: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recently registered students
 * 
 * @param int $days Number of days to look back
 * @param int $limit Maximum number of records to retrieve
 * @return array Returns an array of recently registered students
 */
function get_recent_students($days = 30, $limit = 10) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    s.student_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    s.grade_level,
                    s.school,
                    s.joined_date
                FROM 
                    students s
                JOIN 
                    users u ON s.user_id = u.user_id
                WHERE 
                    s.joined_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND u.account_status = 'active'
                ORDER BY 
                    s.joined_date DESC
                LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error getting recent students: " . $e->getMessage());
        return [];
    }
}

/**
 * Update student's subscription level
 * 
 * @param int $student_id The student ID to update
 * @param string $new_level The new subscription level
 * @return bool Returns true on success, false on failure
 */
function update_student_subscription($student_id, $new_level) {
    try {
        $conn = get_db_connection();
        
        $sql = "UPDATE students 
                SET subscription_level = :new_level 
                WHERE student_id = :student_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':new_level', $new_level);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error updating student subscription: " . $e->getMessage());
        return false;
    }
}

// Example usage for create_student function
/*
$user_data = [
    'email' => 'michael.brown@student.com',
    'password' => 'securepassword123',
    'first_name' => 'Michael',
    'last_name' => 'Brown'
];

$student_data = [
    'grade_level' => '10',
    'school' => 'Central High School',
    'parent_name' => 'David Brown',
    'parent_email' => 'david.brown@example.com',
    'debate_experience' => '1', // Years of experience
    'subscription_level' => 'free'
];

$new_student_id = create_student($user_data, $student_data);
if ($new_student_id) {
    echo "New student created with ID: " . $new_student_id;
} else {
    echo "Failed to create student.";
}
*/
