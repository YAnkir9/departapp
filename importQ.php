<?php 
 
// Load the database configuration file 
include_once 'dbConfig.php'; 
 
// Include PhpSpreadsheet library autoloader 
require_once 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
 
if(isset($_POST['importSubmit'])){ 
     
    // Allowed mime types 
    $excelMimes = array('text/xls', 'text/xlsx', 'application/excel', 'application/vnd.msexcel', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
     
    // Validate whether selected file is a Excel file 
    if(!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $excelMimes)){ 
         
        // If the file is uploaded 
        if(is_uploaded_file($_FILES['file']['tmp_name'])){ 
            $reader = new Xlsx(); 
            $spreadsheet = $reader->load($_FILES['file']['tmp_name']); 
            $worksheet = $spreadsheet->getActiveSheet();  
            $worksheet_arr = $worksheet->toArray(); 
 
            // Remove header row 
            unset($worksheet_arr[0]); 
 
            foreach($worksheet_arr as $row){ 
                $first_name = $row[0]; 
                $last_name = $row[1]; 
                $email = $row[2]; 
                $phone = $row[3]; 
                $status = $row[4]; 
 
                // Check whether member already exists in the database with the same email 
                $prevQuery = "SELECT id FROM members WHERE email = '".$email."'"; 
                $prevResult = $db->query($prevQuery); 
                 
                if($prevResult->num_rows > 0){ 
                    // Update member data in the database 
                    $db->query("UPDATE members SET first_name = '".$first_name."', last_name = '".$last_name."', email = '".$email."', phone = '".$phone."', status = '".$status."', modified = NOW() WHERE email = '".$email."'"); 
                }else{ 
                    // Insert member data in the database 
                    $db->query("INSERT INTO members (first_name, last_name, email, phone, status, created, modified) VALUES ('".$first_name."', '".$last_name."', '".$email."', '".$phone."', '".$status."', NOW(), NOW())"); 
                } 
            } 
             
            $qstring = '?status=succ'; 
        }else{ 
            $qstring = '?status=err'; 
        } 
    }else{ 
        $qstring = '?status=invalid_file'; 
    } 
} 
 
// Redirect to the listing page 
header("Location: index.php".$qstring); 
 
?>
   // Function to insert MCQ result into the mcq_results table
    function insertMCQResult(userId, examId, mcqId, userAnswer, obtainedWeightage) {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // Success, you can handle the response if needed
                    console.log(xhr.responseText);
                } else {
                    // Error handling, handle the error response
                    console.error(xhr.responseText);
                }
            }
        };

        // Prepare the data to send
        var data = new FormData();
        data.append('user_id', userId);
        data.append('Exam_id', examId);
        data.append('mcq_id', mcqId);
        data.append('user_answer', userAnswer);
        data.append('m_obtain', obtainedWeightage);

        // Send the request
        xhr.open('POST', 'insert_mcq_result.php', true);
        xhr.send(data);
    }
