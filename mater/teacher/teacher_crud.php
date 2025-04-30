<?php
/**
 * Teacher CRUD Operations
 * 
 * This file contains functions for handling Create, Read, Update, and Delete
 * operations for teacher accounts in the DebateSkills platform.
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
 * Create a new teacher account
 * 
 * @param array $user_data User data (email, password, first_name, last_name)
 * @param array $teacher_data Teacher specific data (biography, qualification, etc.)
 * @return int|bool Returns the new teacher_id on success, false on failure
 */
function create_teacher($user_data, $teacher_data) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction to ensure both user and teacher records are created
        $conn->beginTransaction();
        
        // Create user record first
        $user_sql = "INSERT INTO users (email, password_hash, first_name, last_name, account_type, account_status) 
                    VALUES (:email, :password_hash, :first_name, :last_name, 'teacher', 'active')";
        
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bindParam(':email', $user_data['email']);
        // Set the password to always be '12345678'
        $password_hash = password_hash('12345678', PASSWORD_DEFAULT);
        $user_stmt->bindParam(':password_hash', $password_hash);
        $user_stmt->bindParam(':first_name', $user_data['first_name']);
        $user_stmt->bindParam(':last_name', $user_data['last_name']);
        $user_stmt->execute();
        
        // Get the new user_id
        $user_id = $conn->lastInsertId();
        
        // Create teacher record
        $teacher_sql = "INSERT INTO teachers (user_id, biography, qualification, specialty, 
                        years_experience, institution, phone_number, office_hours) 
                        VALUES (:user_id, :biography, :qualification, :specialty, 
                        :years_experience, :institution, :phone_number, :office_hours)";
        
        $teacher_stmt = $conn->prepare($teacher_sql);
        $teacher_stmt->bindParam(':user_id', $user_id);
        $teacher_stmt->bindParam(':biography', $teacher_data['biography']);
        $teacher_stmt->bindParam(':qualification', $teacher_data['qualification']);
        $teacher_stmt->bindParam(':specialty', $teacher_data['specialty']);
        $teacher_stmt->bindParam(':years_experience', $teacher_data['years_experience']);
        $teacher_stmt->bindParam(':institution', $teacher_data['institution']);
        $teacher_stmt->bindParam(':phone_number', $teacher_data['phone_number']);
        $teacher_stmt->bindParam(':office_hours', $teacher_data['office_hours']);
        $teacher_stmt->execute();
        
        // Get the new teacher_id
        $teacher_id = $conn->lastInsertId();
        
        // Commit the transaction
        $conn->commit();
        
        return $teacher_id;
    } catch(PDOException $e) {
        // Roll back the transaction if an error occurred
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error creating teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all teachers
 * 
 * @param int $limit Maximum number of records to retrieve
 * @param int $offset Offset for pagination
 * @param string $order_by Field to order by
 * @param string $order_dir Direction of ordering (ASC or DESC)
 * @return array Returns an array of teacher records
 */
function get_all_teachers($limit = 25, $offset = 0, $order_by = 'last_name', $order_dir = 'ASC') {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    t.teacher_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    t.qualification,
                    t.institution,
                    t.specialty,
                    t.years_experience
                FROM 
                    teachers t
                JOIN 
                    users u ON t.user_id = u.user_id
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
        error_log("Error retrieving teachers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get teacher by ID
 * 
 * @param int $teacher_id The teacher ID to lookup
 * @return array|bool Returns the teacher data on success, false on failure
 */
function get_teacher_by_id($teacher_id) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    t.*,
                    u.user_id,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.account_status,
                    u.date_registered,
                    u.last_login
                FROM 
                    teachers t
                JOIN 
                    users u ON t.user_id = u.user_id
                WHERE 
                    t.teacher_id = :teacher_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error retrieving teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Get teacher by user ID
 * 
 * @param int $user_id The user ID to lookup
 * @return array|bool Returns the teacher data on success, false on failure
 */
function get_teacher_by_user_id($user_id) {
    try {
        $conn = get_db_connection();
        
        $sql = "SELECT 
                    t.*,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.account_status,
                    u.date_registered,
                    u.last_login
                FROM 
                    teachers t
                JOIN 
                    users u ON t.user_id = u.user_id
                WHERE 
                    t.user_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error retrieving teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Update teacher information
 * 
 * @param int $teacher_id The teacher ID to update
 * @param array $user_data User data to update (email, first_name, last_name)
 * @param array $teacher_data Teacher specific data to update
 * @return bool Returns true on success, false on failure
 */
function update_teacher($teacher_id, $user_data = [], $teacher_data = []) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Get the user_id for this teacher
        $get_user_id_sql = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Teacher not found
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
        
        // Update teacher data if provided
        if (!empty($teacher_data)) {
            $teacher_update_fields = [];
            $teacher_params = [];
            
            // Build dynamic update query based on provided fields
            foreach ($teacher_data as $key => $value) {
                if (in_array($key, ['biography', 'qualification', 'specialty', 'years_experience', 'institution', 'phone_number', 'office_hours'])) {
                    $teacher_update_fields[] = "$key = :$key";
                    $teacher_params[":$key"] = $value;
                }
            }
            
            if (!empty($teacher_update_fields)) {
                $teacher_sql = "UPDATE teachers SET " . implode(", ", $teacher_update_fields) . " WHERE teacher_id = :teacher_id";
                $teacher_stmt = $conn->prepare($teacher_sql);
                $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
                
                foreach ($teacher_params as $param => $value) {
                    $teacher_stmt->bindValue($param, $value);
                }
                
                $teacher_stmt->execute();
            }
        }
        
        // Update password if provided
        if (isset($user_data['password']) && !empty($user_data['password'])) {
            $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $pwd_sql = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
            $pwd_stmt = $conn->prepare($wd_sql);
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
        error_log("Error updating teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Deactivate a teacher account
 * 
 * @param int $teacher_id The teacher ID to deactivate
 * @return bool Returns true on success, false on failure
 */
function deactivate_teacher($teacher_id) {
    try {
        $conn = get_db_connection();
        
        // Get the user_id for this teacher
        $get_user_id_sql = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Teacher not found
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
        error_log("Error deactivating teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Activate a teacher account
 * 
 * @param int $teacher_id The teacher ID to activate
 * @return bool Returns true on success, false on failure
 */
function activate_teacher($teacher_id) {
    try {
        $conn = get_db_connection();
        
        // Get the user_id for this teacher
        $get_user_id_sql = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Teacher not found
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
        error_log("Error activating teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a teacher account (completely removes both teacher and user records)
 * 
 * @param int $teacher_id The teacher ID to delete
 * @return bool Returns true on success, false on failure
 */
function delete_teacher($teacher_id) {
    try {
        $conn = get_db_connection();
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Get the user_id for this teacher
        $get_user_id_sql = "SELECT user_id FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($get_user_id_sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            // Teacher not found
            return false;
        }
        
        $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
        
        // Delete teacher record
        $delete_teacher_sql = "DELETE FROM teachers WHERE teacher_id = :teacher_id";
        $stmt = $conn->prepare($delete_teacher_sql);
        $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
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
        error_log("Error deleting teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Search for teachers based on various criteria
 * 
 * @param array $search_params Search parameters (name, qualification, specialty, etc.)
 * @param int $limit Maximum number of records to retrieve
 * @param int $offset Offset for pagination
 * @return array Returns an array of matching teacher records
 */
function search_teachers($search_params, $limit = 25, $offset = 0) {
    try {
        $conn = get_db_connection();
        
        $conditions = [];
        $params = [];
        
        // Build search conditions based on provided parameters
        if (isset($search_params['name']) && !empty($search_params['name'])) {
            $conditions[] = "(u.first_name LIKE :name OR u.last_name LIKE :name)";
            $params[':name'] = '%' . $search_params['name'] . '%';
        }
        
        if (isset($search_params['qualification']) && !empty($search_params['qualification'])) {
            $conditions[] = "t.qualification LIKE :qualification";
            $params[':qualification'] = '%' . $search_params['qualification'] . '%';
        }
        
        if (isset($search_params['specialty']) && !empty($search_params['specialty'])) {
            $conditions[] = "t.specialty LIKE :specialty";
            $params[':specialty'] = '%' . $search_params['specialty'] . '%';
        }
        
        if (isset($search_params['institution']) && !empty($search_params['institution'])) {
            $conditions[] = "t.institution LIKE :institution";
            $params[':institution'] = '%' . $search_params['institution'] . '%';
        }
        
        if (isset($search_params['min_experience']) && !empty($search_params['min_experience'])) {
            $conditions[] = "t.years_experience >= :min_experience";
            $params[':min_experience'] = $search_params['min_experience'];
        }
        
        // Always only show active accounts
        $conditions[] = "u.account_status = 'active'";
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "SELECT 
                    t.teacher_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    t.qualification,
                    t.institution,
                    t.specialty,
                    t.years_experience
                FROM 
                    teachers t
                JOIN 
                    users u ON t.user_id = u.user_id
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
        error_log("Error searching teachers: " . $e->getMessage());
        return [];
    }
}

/**
 * Count total teachers matching search criteria
 * 
 * @param array $search_params Search parameters (same as search_teachers)
 * @return int Returns the total count of matching records
 */
function count_teachers($search_params = []) {
    try {
        $conn = get_db_connection();
        
        $conditions = [];
        $params = [];
        
        // Build search conditions based on provided parameters
        if (isset($search_params['name']) && !empty($search_params['name'])) {
            $conditions[] = "(u.first_name LIKE :name OR u.last_name LIKE :name)";
            $params[':name'] = '%' . $search_params['name'] . '%';
        }
        
        if (isset($search_params['qualification']) && !empty($search_params['qualification'])) {
            $conditions[] = "t.qualification LIKE :qualification";
            $params[':qualification'] = '%' . $search_params['qualification'] . '%';
        }
        
        if (isset($search_params['specialty']) && !empty($search_params['specialty'])) {
            $conditions[] = "t.specialty LIKE :specialty";
            $params[':specialty'] = '%' . $search_params['specialty'] . '%';
        }
        
        if (isset($search_params['institution']) && !empty($search_params['institution'])) {
            $conditions[] = "t.institution LIKE :institution";
            $params[':institution'] = '%' . $search_params['institution'] . '%';
        }
        
        if (isset($search_params['min_experience']) && !empty($search_params['min_experience'])) {
            $conditions[] = "t.years_experience >= :min_experience";
            $params[':min_experience'] = $search_params['min_experience'];
        }
        
        // Always only count active accounts
        $conditions[] = "u.account_status = 'active'";
        
        $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "SELECT 
                    COUNT(*) as total
                FROM 
                    teachers t
                JOIN 
                    users u ON t.user_id = u.user_id
                $where_clause";
        
        $stmt = $conn->prepare($sql);
        
        // Bind all search parameters
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        error_log("Error counting teachers: " . $e->getMessage());
        return 0;
    }
}

// Example usage for create_teacher function
/*
$user_data = [
    'email' => 'sarah.johnson@example.com',
    'password' => 'securepassword123',
    'first_name' => 'Sarah',
    'last_name' => 'Johnson'
];

$teacher_data = [
    'biography' => 'Experienced debate coach with a passion for teaching.',
    'qualification' => 'M.A. in Communication Studies',
    'specialty' => 'Parliamentary Debate',
    'years_experience' => 8,
    'institution' => 'Northside High School',
    'phone_number' => '555-123-4567',
    'office_hours' => 'Monday and Wednesday 3-5pm'
];

$new_teacher_id = create_teacher($user_data, $teacher_data);
if ($new_teacher_id) {
    echo "New teacher created with ID: " . $new_teacher_id;
} else {
    echo "Failed to create teacher.";
}
*/