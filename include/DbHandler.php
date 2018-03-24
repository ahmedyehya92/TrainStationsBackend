<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password, $profile_pic, $device) {
        require_once 'PassHash.php';
        require_once 'DecodeImage.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
            $profile_pic_url = NULL;
            if ($profile_pic != NULL)
            {
            $profile_pic_url = DecodeImage::upload($profile_pic);
            }

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, profile_picture, api_key, device, status) values(?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssss", $name, $email, $password_hash, $profile_pic_url, $api_key, $device);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                
                $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
            return $user;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    
    public function addProfilePic($email, $image) {
        
        require_once 'DecodeImage.php';
        $profile_pic_url = NULL;
        
        if ($image != NULL)
            {
            $profile_pic_url = DecodeImage::upload($image);
            }
        
            if ($profile_pic_url != FALSE)
            {
                $stmt = $this->conn->prepare("UPDATE users SET profile_picture=? WHERE email=?");
                $stmt->bind_param("ss", $profile_pic_url, $email);
                $result = $stmt->execute();
                $stmt->close();

                if ($result) {
                
                    return TRUE;
                } else {
                
                    return FALSE;
                 }
           
            } else {
               return FALSE;
           }
        
    }
    
    
    public function updateToken($token, $email) {
        
       
            // insert query
       
            $stmt = $this->conn->prepare("UPDATE users SET token = ? WHERE email = ?");
            $stmt->bind_param("ss", $token, $email);

            $result = $stmt->execute();

            $stmt->close();
        
 
 
        return $result;
    }
    
    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /*-------------- following table methods---------------- */
    
    
    public function  isFollowed ($user_id, $chat_room_id)
    {
                $stmt = $this->conn->prepare("SELECT following.user_id FROM following WHERE following.user_id = ? AND following.chat_room_id = ?");
                $stmt->bind_param("ii", $user_id, $chat_room_id);
                if ($stmt->execute()) 
                {
                    
                    $stmt->bind_result($userid);
            $stmt->fetch();
            $isFollwed = array();
            $isFollwed["user_id"] = $userid;
           
                   $stmt->close();
                   return $isFollwed;
                }
                else {
            return NULL;
        }
                
                
    }
    
    
    public function addUserFollow($user_id, $chatroom_id) {
            
            $stmtp = $this->conn->prepare("INSERT INTO following (user_id,chat_room_id) VALUES (?,?)");
            $stmtp->bind_param("ii", $user_id, $chatroom_id);

            $result = $stmtp->execute();

            $stmtp->close();
            if ($result) {
                
                return "done";
            } else {
                
                return NULL;
            }
    }
    
    public function deleteUserFollow($user_id, $chat_room_id) {
            
            $stmtp = $this->conn->prepare("DELETE FROM following WHERE user_id=? AND chat_room_id=?");
            $stmtp->bind_param("ii", $user_id, $chat_room_id);

            $result = $stmtp->execute();

            $stmtp->close();
            if ($result) {
                
                return "done";
            } else {
                
                return NULL;
            }
    }
    
    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    
    
    /* ------------- messages table methods --------------- */
    
    // messaging in a chat room / to persional message
    public function addMessage($user_id, $chat_room_id, $message) {
        $response = array();
 
        $stmt = $this->conn->prepare("INSERT INTO messages (chat_room_id, user_id, message) values(?, ?, ?)");
        $stmt->bind_param("iis", $chat_room_id, $user_id, $message);
 
        $result = $stmt->execute();
 
        if ($result) {
            $response['error'] = false;
 
            // get the message
            $message_id = $this->conn->insert_id;
            $stmt = $this->conn->prepare("SELECT message_id, user_id, chat_room_id, message, created_at FROM messages WHERE message_id = ?");
            $stmt->bind_param("i", $message_id);
            if ($stmt->execute()) {
                $stmt->bind_result($message_id, $user_id, $chat_room_id, $message, $created_at);
                $stmt->fetch();
                $tmp = array();
                $tmp['message_id'] = $message_id;
                $tmp['chat_room_id'] = $chat_room_id;
                $tmp['message'] = $message;
                $tmp['created_at'] = $created_at;
                $response['message'] = $tmp;
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'Failed send message';
        }
 
        return $response;
    }
    
    
    
    public function trainBy2Stations ($departure_station_id, $arrival_station_id, $class_id )
    {
        $stmt1 = $this->conn->prepare("SET @start = ?, @finish = ?, @class = ?;");
        $stmt1->bind_param("iii", $departure_station_id, $arrival_station_id, $class_id);
        $stmt1->execute();
        
        $stmt = $this->conn->prepare("
          (SELECT trains.train_id, trains.train_num, classes.class_name, stations_train.arrival 
	FROM trains JOIN classes ON classes.class_id = trains.class JOIN stations_train ON trains.train_id = stations_train.train 
    WHERE @start<@finish AND @class = 0 AND trains.direction =1 AND stations_train.station IN(@start,@finish) 
    GROUP BY stations_train.train HAVING COUNT(*) =2)
    
    UNION
    
    (SELECT trains.train_id, trains.train_num, classes.class_name, stations_train.arrival
	FROM trains JOIN classes ON classes.class_id = trains.class JOIN stations_train ON trains.train_id = stations_train.train 
    WHERE @start>@finish AND @class = 0 AND trains.direction =0 AND stations_train.station IN(@start,@finish) 
    GROUP BY stations_train.train HAVING COUNT(*) =2)
    
    UNION
    
    (SELECT trains.train_id, trains.train_num, classes.class_name, stations_train.arrival 
	FROM trains JOIN classes ON classes.class_id = trains.class JOIN stations_train ON trains.train_id = stations_train.train 
    WHERE @start<@finish AND @class != 0 AND trains.class = @class AND trains.direction =1 AND stations_train.station IN(@start,@finish) 
    GROUP BY stations_train.train HAVING COUNT(*) =2)

    UNION

    (SELECT trains.train_id, trains.train_num, classes.class_name, stations_train.arrival 
	FROM trains JOIN classes ON classes.class_id = trains.class JOIN stations_train ON trains.train_id = stations_train.train 
    WHERE @start>@finish AND @class != 0 AND trains.class = @class AND trains.direction =0 AND stations_train.station IN(@start,@finish) 
    GROUP BY stations_train.train HAVING COUNT(*) =2)");
        
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }



    public function stationsByTrain($train_id)
    {
        $stmt = $this->conn->prepare("SELECT stations.station_name, stations_train.arrival 
    FROM stations_train JOIN stations ON stations.station_id = stations_train.station 
    WHERE stations_train.train = ?");
        $stmt->bind_param("i", $train_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

        public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>
