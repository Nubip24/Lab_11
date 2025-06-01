<?php
require_once "_db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $name = trim($_POST['name'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (!$name || !$capacity || !$status) {
        echo json_encode(["result" => "ERROR", "message" => "Всі поля є обов'язковими"]);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO rooms (NAME, capacity, STATUS) VALUES (:name, :capacity, :status)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status);
        $stmt->execute();

        echo json_encode(["result" => "OK"]);
    } catch (PDOException $e) {
        echo json_encode(["result" => "ERROR", "message" => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <title>Додати нову кімнату</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 15px;
            background: #fff;
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            margin-top: 4px;
            font-size: 14px;
        }
        button {
            margin-top: 15px;
            padding: 10px 18px;
            font-size: 16px;
            cursor: pointer;
        }
        #error {
            color: red;
            margin-top: 12px;
            min-height: 20px;
        }
    </style>

    <script src="js/daypilot-all.min.js"></script>
</head>
<body>
    <h3>Додати нову кімнату</h3>

    <form id="roomForm" autocomplete="off">
        <label for="name">Назва кімнати:</label>
        <input type="text" id="name" name="name" required minlength="2" maxlength="255" />

        <label for="capacity">Місткість (кількість ліжок):</label>
        <select id="capacity" name="capacity" required>
            <option value="">Виберіть...</option>
            <option value="1">1 (Одномісна)</option>
            <option value="2">2 (Двомісна)</option>
            <option value="4">4 (Сімейна)</option>
        </select>

        <label for="status">Статус кімнати:</label>
        <select id="status" name="status" required>
            <option value="">Виберіть...</option>
            <option value="чиста">Чиста</option>
            <option value="брудна">Брудна</option>
            <option value="прибирається">Прибирається</option>
        </select>

        <button type="submit">Додати</button>
        <div id="error"></div>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $("#roomForm").on("submit", function(e) {
            e.preventDefault();
            $("#error").text("");

            $.ajax({
                url: "room_new.php",
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {
                    if (response.result === "OK") {
                        DayPilot.Modal.close({ result: "OK" });
                    } else {
                        $("#error").text(response.message || "Сталася помилка");
                    }
                },
                error: function() {
                    $("#error").text("Помилка сервера. Спробуйте пізніше.");
                }
            });
        });
    </script>
</body>
</html>
