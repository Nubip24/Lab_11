<?php
require_once '_db.php';

class Result {}

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt FROM reservations 
        WHERE NOT ((end <= :start) OR (start >= :end)) 
          AND id <> :id 
          AND room_id = :resource
    ");
    
    $stmt->bindParam(':start', $_POST['newStart']);
    $stmt->bindParam(':end', $_POST['newEnd']);
    $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
    $stmt->bindParam(':resource', $_POST['newResource'], PDO::PARAM_INT);
    $stmt->execute();
    
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $response = new Result();
        $response->result = 'Error';
        $response->message = 'Це бронювання перетинається з наявним.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE reservations 
        SET start = :start, end = :end, room_id = :resource 
        WHERE id = :id
    ");

    $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
    $stmt->bindParam(':start', $_POST['newStart']);
    $stmt->bindParam(':end', $_POST['newEnd']);
    $stmt->bindParam(':resource', $_POST['newResource'], PDO::PARAM_INT);
    $stmt->execute();

    $response = new Result();
    $response->result = 'OK';
    $response->message = 'Бронювання оновлено успішно.';

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    $response = new Result();
    $response->result = 'Error';
    $response->message = 'Помилка сервера: ' . $e->getMessage();
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
