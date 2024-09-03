<?php
error_reporting(E_ALL);
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "Exceed";

$data = new mysqli($host, $user, $password, $db);

if ($data->connect_error) {
    die("Connection failed: " . $data->connect_error);
}

$inappropriate_words = ['fuck', 'sex', 'fuckoff'];

function contains_inappropriate_words($text, $inappropriate_words) {
    foreach ($inappropriate_words as $word) {
        if (stripos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}

if (isset($_POST['add_question'])) {
    $question = $data->real_escape_string($_POST['question']);
    
    if (contains_inappropriate_words($question, $inappropriate_words)) {
        echo "<script>alert('Your question contains inappropriate language and cannot be submitted.');</script>";
    } else {
        $stmt = $data->prepare("INSERT INTO Exceed1 (question) VALUES (?)");
        $stmt->bind_param("s", $question);

        if ($stmt->execute()) {
            echo "<script>alert('Question is submitted and will be answered.');</script>";
        } else {
            echo "Data not uploaded: " . $stmt->error;
        }

        $stmt->close();

        header("Refresh: 0");
        exit();
    }
}

if (isset($_POST['emoji']) && isset($_POST['answer_id'])) {
    $emoji = $_POST['emoji'];
    $answer_id = (int)$_POST['answer_id'];

    $emoji_column_map = [
        'thumbs_up' => 'thumbs_up',
        'thumbs_down' => 'thumbs_down',
        'heart' => 'heart'
    ];

    if (array_key_exists($emoji, $emoji_column_map)) {
        $column = $emoji_column_map[$emoji];

        if (!isset($SESSION["clicked{$emoji}_{$answer_id}"])) {
            $stmt = $data->prepare("INSERT INTO AnswerReactions (answer_id, $column) VALUES (?, 1)
                                    ON DUPLICATE KEY UPDATE $column = $column + 1");
            $stmt->bind_param("i", $answer_id);
            $stmt->execute();
            $stmt->close();

            $SESSION["clicked{$emoji}_{$answer_id}"] = true;

            $stmt = $data->prepare("SELECT $column FROM AnswerReactions WHERE answer_id = ?");
            $stmt->bind_param("i", $answer_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            echo $count;
            exit();
        } else {
            echo "0";
            exit();
        }
    }
}
$query = "SELECT Exceed1.id AS question_id, Exceed1.question, Answers.answer_id, Answers.answer, Answers.image_path,
                 IFNULL(AnswerReactions.thumbs_up, 0) AS thumbs_up, 
                 IFNULL(AnswerReactions.thumbs_down, 0) AS thumbs_down, 
                 IFNULL(AnswerReactions.heart, 0) AS heart
          FROM Exceed1 
          LEFT JOIN Answers ON Exceed1.id = Answers.question_id 
          LEFT JOIN AnswerReactions ON Answers.answer_id = AnswerReactions.answer_id
          ORDER BY Exceed1.id, Answers.answer_id";
$result = $data->query($query);

$questions = [];
$answer_counts = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['question_id'];
    if (!isset($questions[$id])) {
        $questions[$id] = [
            'question' => $row['question'],
            'answers' => []
        ];
    }
    if ($row['answer']) {
        $questions[$id]['answers'][] = [
            'answer_id' => $row['answer_id'],
            'text' => $row['answer'],
            'image' => $row['image_path'],
            'thumbs_up' => $row['thumbs_up'],
            'thumbs_down' => $row['thumbs_down'],
            'heart' => $row['heart']
        ];
    }
}

foreach ($questions as $id => $question) {
    $answer_counts[$id] = count($question['answers']);
}

$data->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="exceed.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .content {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            text-align: center;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        .question-card, .in-progress-card {
            background: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .question-card h3, .in-progress-card h3 {
            margin-top: 0;
            color: #333;
        }
        .question-card p {
            color: #666;
        }
        .faq-section, .in-progress-section {
            margin-top: 30px;
        }
        .answer-box {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .answer-box img {
            max-width: 100%;
            height: auto;
            display: block;
            margin-top: 10px;
        }
        .more-answers {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
            font-size: 16px;
        }
        .more-answers:hover {
            text-decoration: none;
        }
        .additional-answers-container {
            max-height: 0; 
            overflow: hidden;
            transition: max-height 0.5s ease-out; 
        }
        .additional-answers-container.show {
            max-height: 1000px; 
        }
        .emoji-container {
            margin-top: 10px;
            display: flex;
            justify-content: space-around;
            font-size: 20px;
        }
        .emoji-container span {
            cursor: pointer;
        }
        .contain-back {
            background-color: white;
        }

        .heading1 {
            font-family: "italic";
            color: gold;
            background-image: linear-gradient(#fa70e1, #64fab4);
            border-style: solid;
            border-width: 5px;
            border-color: gold;
            border-radius: 15px;
        }

        .heading {
            text-align: center;
            font-family: "italic";
        }

        .heading3 {
            text-align: center;
            font-family: "italic";
            font-size: 30px;
            text-decoration-line: underline;
        }

        .sep {
            height: 50px;
            width: 50px;
        }

        .main-card {
            background-color: white;
            border-style: solid;
            border-width: 1px;
            border-color: #e5eaf4;
            border-radius: 15px;
            margin: 90px;
            margin-top: 40px;
        }

        .main-card1 {
            background-color: white;
            border-style: solid;
            border-width: 1px;
            border-color: #e5eaf4;
            border-radius: 15px;
        }

        .main-card2 {
            background-color: white;
            border-style: solid;
            border-width: 1px;
            border-color: #e5eaf4;
            border-radius: 15px;
        }

        .button {
            text-align: center;
            padding: 55px;
            margin: 40px;
        }

        .button2 {
            text-align: center;
        }

        .button1 {
            background-color: lightblue;
            padding: 5px;
            border-radius: 5px;
        }

        .second {
            margin-top: 140px;
        }

        .text-card {
            text-align: center;
            font-size: 20px;
        }

        .card-heading {
            font-size: 20px;
            font-family: "Roboto";
        }

        .iare-image {
            width: 80%;
            margin-top:15px;
            margin-bottom:15px;
            object-fit: cover;
            border-radius: 20px;
        }
        .heading4 {
            font-family: "italic";
            font-size: 15px;
        }
        .custom-button {
            color: white;
            background-image: linear-gradient(#d0b200, #a58d00);
            width: 160px;
            height: 45px;
            border-width: 0;
            border-radius: 8px;
            margin-right: 10px;
        }

        .thanking-customers-section-modal-title {
            color: #d0b200;
            font-weight: 800;
        }
        .emoji-container {
            margin-top: 10px;
            display: flex;
            justify-content: flex-start; /* Aligns emojis to the left */
            gap: 10px; /* Adds spacing between emojis */
            font-size: 20px;
            align-items: center; /* Aligns emojis vertically */
        }
        .search{
            padding-left:20px;
        }

    </style>
    <script>
        function toggleAnswers(questionId) {
            const container = document.querySelector('.additional-answers-container-' + questionId);
            const moreAnswersButton = document.getElementById('more-answers-' + questionId);

            if (container) {
                if (container.classList.contains('show')) {
                    container.classList.remove('show');
                    moreAnswersButton.textContent = 'More Answers';
                } else {
                    container.classList.add('show');
                    moreAnswersButton.textContent = 'Less Answers';
                }
            }
        }

        function handleEmojiClick(answerId, emoji) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo $_SERVER['PHP_SELF']; ?>", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById(emoji + "-" + answerId).innerText =
                    document.getElementById(emoji + "-" + answerId).innerText = xhr.responseText;
                }
            };
            xhr.send("emoji=" + encodeURIComponent(emoji) + "&answer_id=" + encodeURIComponent(answerId));
        }
    </script>
</head>
<body>
    <div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div class="container">
                <a class="navbar-brand " href="#">
                    <img src="https://media.licdn.com/dms/image/C4D0BAQGny_rcc2klag/company-logo_200_200/0/1631364840498?e=2147483647&v=beta&t=atkjcpbcro-0VwaPA2mqvLc7xcyuYkf5RITm4fAKtTo" class="sep" />
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav ml-auto pt-3">
                    <form class="d-flex ml-auto" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
                        <input class="form-control me-2" type="search" name="query" placeholder="Search" aria-label="Search" required>
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>

                        <a class="nav-link active" id="navItem1" href="#homesection">
                            <i class="fas fa-home "></i> HOME
                            <span class="sr-only">(current)</span>
                        </a>
                        <a class="nav-link" href="#QUPsection" id="navItem2">
                            <i class="fas fa-book"></i> QUP
                        </a>
                        <a class="nav-link" href="#FAQsection" id="navItem3">
                            <i class="fas fa-book"></i> FAQ
                        </a>
                        <a class="nav-link" href="#team" id="navItem4">
                            <i class="fas fa-users "></i> TEAM
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div id="homesection" class="container second">
            <div class="row">
                <div class="col-12 col-xl-4">
                <div class="content  mt-2">
                <div class="text-card heading3">
                    <h1 class="heading3 mt-3">Enter Your Question</h1>
                </div>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                            <div class="form-group pt-5 ml-5 mr-5">
                                <label for="question"></label>
                                <input type="text" class="form-control" name="question" id="question" placeholder="Type your question here" required>
                            </div>
                            <div class="text-card mb-3">
                                <input type="submit" class="custom-button pl-3 pr-3" name="add_question" value="Add Question">
                            </div>
                        </form>
                    </div>
                    <div class="shadow main-card1 mb-5  mt-5 p-3">
                    <div id="QUPsection" class="in-progress-section">
                    <div class="text-card heading3 pb-4">
                        <h4>Questions Under Progress</h4>
                    </div>
                        <?php
                        $progress_found = false;
                        foreach ($questions as $id => $question) {
                            if (isset($answer_counts[$id]) && $answer_counts[$id] <= 2) { 
                                $progress_found = true;
                                echo '<div class="in-progress-card">';
                                echo '<h6>Question: ' . htmlspecialchars($question['question']) . '</h6>';
                                echo '</div>';
                            }
                        }
                        if (!$progress_found) {
                            echo "<p>No questions under progress.</p>";
                        }
                        ?>
                        </div>
                    </div>
                </div>
                <div id="FAQsection" class="col-12 col-xl-8">
                    <div class="shadow main-card1 mb-5 pt-3 pl-3">
                    <div class="faq-section">
                    <div class="text-card heading3">
                        <h2>Frequently Asked Questions</h2>
                    </div>
                        <?php
                        $questions_found = false;
                        foreach ($questions as $id => $question) {
                            if (isset($answer_counts[$id]) && $answer_counts[$id] > 2) { 
                                $questions_found = true;
                                echo '<div class="question-card">';
                                echo '<h3>Question: ' . htmlspecialchars($question['question']) . '</h3>';

                                $first_answer = $question['answers'][0];
                                echo '<div class="answer-box">' . htmlspecialchars($first_answer['text']) . '</div>';
                                if ($first_answer['image']) {
                                    echo '<div style="text-align: center;">';
                                    echo '<img src="' . htmlspecialchars($first_answer['image']) . '" class="iare-image" alt="Answer Image">';
                                    echo '</div>';
                                }
                                

                                echo '<div class="emoji-container">';
                                echo '<span onclick="handleEmojiClick(' . $first_answer['answer_id'] . ', \'thumbs_up\')">👍</span> <span id="thumbs_up-' . $first_answer['answer_id'] . '">' . $first_answer['thumbs_up'] . '</span>';
                                echo '<span onclick="handleEmojiClick(' . $first_answer['answer_id'] . ', \'thumbs_down\')">👎</span> <span id="thumbs_down-' . $first_answer['answer_id'] . '">' . $first_answer['thumbs_down'] . '</span>';
                                echo '<span onclick="handleEmojiClick(' . $first_answer['answer_id'] . ', \'heart\')">❤</span> <span id="heart-' . $first_answer['answer_id'] . '">' . $first_answer['heart'] . '</span>';
                                echo '</div>';


                                if (count($question['answers']) > 1) {
                                    echo '<div class="additional-answers-container additional-answers-container-' . $id . '">';
                                    for ($i = 1; $i < count($question['answers']); $i++) {
                                        $answer = $question['answers'][$i];
                                        echo '<div class="answer-box">' . htmlspecialchars($answer['text']) . '</div>';
                                        if ($answer['image']) {
                                            echo '<img src="' . htmlspecialchars($answer['image']) . '" alt="Answer Image">';
                                        }
                                        echo '<div class="emoji-container">';
                                        echo '<span onclick="handleEmojiClick(' . $answer['answer_id'] . ', \'thumbs_up\')">👍</span> <span id="thumbs_up-' . $answer['answer_id'] . '">' . $answer['thumbs_up'] . '</span>';
                                        echo '<span onclick="handleEmojiClick(' . $answer['answer_id'] . ', \'thumbs_down\')">👎</span> <span id="thumbs_down-' . $answer['answer_id'] . '">' . $answer['thumbs_down'] . '</span>';
                                        echo '<span onclick="handleEmojiClick(' . $answer['answer_id'] . ', \'heart\')">❤</span> <span id="heart-' . $answer['answer_id'] . '">' . $answer['heart'] . '</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                    echo '<span class="more-answers" id="more-answers-' . $id . '" onclick="toggleAnswers(' . $id . ')">More Answers</span>';
                                }

                                echo '</div>';
                            }
                        }
                        if (!$questions_found) {
                            echo "<p>No questions and answers found.</p>";
                        }
                        ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
<footer id="team" class="bg-dark text-white mt-5">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-6">
                <h5>Chakri Shabad</h5>
                <p><strong>Section:</strong> Backend Development</p>
                <p>......</p>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div>
                    <h5>Karthik Ujgiri</h5>
                    <p><strong>Section:</strong> Frontend Development</p>
                    <p>......</p>
                </div>
            </div>
        </div>
    </div>
</footer>
</html>
