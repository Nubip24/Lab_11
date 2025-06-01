<!DOCTYPE html> 
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <title>Бронювання кімнат в готелі</title>

    <!-- Підключення бібліотек -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/daypilot-all.min.js"></script>

    <style>
        #dp {
            width: 100%;
            height: 600px;
            margin: 20px auto;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }
        header, footer {
            background-color: #f0f0f0;
            padding: 10px 20px;
            text-align: center;
        }
        footer address {
            font-style: normal;
        }
        .scheduler_default_rowheader_inner {
            border-right: 1px solid #ccc;
        }
        .scheduler_default_rowheader.scheduler_default_rowheadercol2 {
            background: #fff;
        }
        .scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner {
            background-color: transparent;
            border-left: 5px solid #1a9d13;
            border-right: 0;
        }
        .status_dirty.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner {
            border-left: 5px solid #ea3624;
        }
        .status_cleanup.scheduler_default_rowheadercol2 .scheduler_default_rowheader_inner {
            border-left: 5px solid #f9ba25;
        }
        #filterBox {
            text-align: center;
            margin: 10px;
        }
        #addRoomBtn {
            margin: 10px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<header>
    <h1>HTML5 Бронювання кімнат в готелі (JavaScript/PHP)</h1>
    <p>AJAX-календар з JavaScript/HTML5/jQuery</p>
</header>

<main>
    <div id="filterBox">
        <label for="filter">Показати кімнати:</label>
        <select id="filter">
            <option value="0">Всі</option>
            <option value="1">Одномісні</option>
            <option value="2">Двомісні</option>
            <option value="4">Сімейні</option>
        </select>

        <!-- Кнопка для додавання кімнати -->
        <button id="addRoomBtn">Додати нову кімнату</button>
    </div>

    <div id="dp"></div>
</main>

<footer>
    <address>(с) Автор: студент ПЗіС-24005м, Щербан Петро-Еммануїл Петрович</address>
</footer>

<script>
    var dp = new DayPilot.Scheduler("dp");

    dp.startDate = new DayPilot.Date("2025-06-01");
    dp.days = dp.startDate.daysInMonth();

    dp.scale = "Day";
    dp.timeHeaders = [
        { groupBy: "Month", format: "MMMM yyyy" },
        { groupBy: "Day", format: "d" }
    ];

    dp.rowHeaderColumns = [
        { title: "Кімната", width: 80 },
        { title: "Місткість", width: 80 },
        { title: "Статус", width: 80 }
    ];

    dp.allowEventOverlap = false;

    dp.onBeforeResHeaderRender = function(args) {
        var beds = function(count) {
            return count + " ліжко" + (count > 1 ? "в" : "");
        };
        args.resource.columns[0].html = args.resource.name;
        args.resource.columns[1].html = beds(args.resource.capacity);
        args.resource.columns[2].html = args.resource.status;

        switch (args.resource.status.toLowerCase()) {
            case "брудна":
                args.resource.cssClass = "status_dirty";
                break;
            case "прибирається":
                args.resource.cssClass = "status_cleanup";
                break;
        }
    };

    dp.onTimeRangeSelected = function(args) {
        var modal = new DayPilot.Modal({
            onClosed: function(modalArgs) {
                dp.clearSelection();
                if (modalArgs.result && modalArgs.result.result === "OK") {
                    loadEvents();
                }
            }
        });
        modal.showUrl("new.php?start=" + args.start.toString() + "&end=" + args.end.toString() + "&resource=" + args.resource);
    };

    dp.onEventClick = function(args) {
        var modal = new DayPilot.Modal({
            onClosed: function(modalArgs) {
                if (modalArgs.result && modalArgs.result.result === "OK") {
                    loadEvents();
                }
            }
        });
        modal.showUrl("edit.php?id=" + args.e.id());
    };

    dp.onEventMoved = function(args) {
        $.post("backend_move.php", {
            id: args.e.id(),
            newStart: args.newStart.toString(),
            newEnd: args.newEnd.toString(),
            newResource: args.newResource
        }, function(data) {
            if (data.result === "OK") {
                dp.message("Бронювання успішно оновлено");
                loadEvents();
            } else {
                dp.message(data.message);
                loadEvents();
            }
        });
    };

    // Додавання підтримки видалення бронювання
    dp.eventDeleteHandling = "Update";
    dp.onEventDeleted = function(args) {
        $.post("backend_delete.php", { id: args.e.id() }, function() {
            dp.message("Бронювання видалено.");
        });
    };

    dp.onBeforeEventRender = function(args) {
        var start = new DayPilot.Date(args.e.start);
        var end = new DayPilot.Date(args.e.end);
        var today = DayPilot.Date.today();
        var now = new DayPilot.Date();

        args.e.html = args.e.text + " (" + start.toString("M/d/yyyy") + " - " + end.toString("M/d/yyyy") + ")";

        switch (args.e.status) {
            case "New":
                var in2days = today.addDays(1);
                if (start < in2days) {
                    args.e.barColor = 'red';
                    args.e.toolTip = 'Застаріле (не підтверджено вчасно)';
                } else {
                    args.e.barColor = 'orange';
                    args.e.toolTip = 'Новий';
                }
                break;
            case "Confirmed":
                var arrivalDeadline = today.addHours(18);
                if (start < today || (start.getDatePart() === today.getDatePart() && now > arrivalDeadline)) {
                    args.e.barColor = "#f41616";
                    args.e.toolTip = 'Пізнє прибуття';
                } else {
                    args.e.barColor = "green";
                    args.e.toolTip = "Підтверджено";
                }
                break;
            case 'Arrived':
                var checkoutDeadline = today.addHours(10);
                if (end < today || (end.getDatePart() === today.getDatePart() && now > checkoutDeadline)) {
                    args.e.barColor = "#f41616";
                    args.e.toolTip = "Пізній виїзд";
                } else {
                    args.e.barColor = "#1691f4";
                    args.e.toolTip = "Прибув";
                }
                break;
            case 'CheckedOut':
                args.e.barColor = "gray";
                args.e.toolTip = "Перевірено";
                break;
            default:
                args.e.toolTip = "Невизначений стан";
                break;
        }

        args.e.html += "<br /><span style='color:gray'>" + args.e.toolTip + "</span>";

        var paid = args.e.paid || 0;
        var paidColor = "#aaaaaa";

        args.e.areas = [
            { bottom: 10, right: 4, html: "<div style='color:" + paidColor + "; font-size: 8pt;'>Оплачено: " + paid + "%</div>", v: "Visible" },
            { left: 4, bottom: 8, right: 4, height: 2, html: "<div style='background-color:" + paidColor + "; height: 100%; width:" + paid + "%'></div>", v: "Visible" }
        ];
    };

    function loadResources() {
        var selectedCapacity = $("#filter").val();
        $.post("backend_rooms.php", { capacity: selectedCapacity }, function(data) {
            dp.resources = data;
            dp.update();
            loadEvents();
        });
    }

    function loadEvents() {
        var start = dp.visibleStart().toString();
        var end = dp.visibleEnd().toString();
        $.post("backend_events.php", { start: start, end: end }, function(data) {
            dp.events.list = data;
            dp.update();
        });
    }

    $(document).ready(function() {
        $("#filter").change(function() {
            loadResources();
        });

        // Обробник кнопки "Додати нову кімнату"
        $("#addRoomBtn").click(function() {
            var modal = new DayPilot.Modal({
                onClosed: function(modalArgs) {
                    if (modalArgs.result && modalArgs.result.result === "OK") {
                        loadResources(); // Підвантажити оновлений список кімнат
                        dp.message("Кімнату додано");
                    }
                }
            });
            modal.showUrl("room_new.php");
        });

        dp.init();
        loadResources();
    });
</script>

</body>
</html>
