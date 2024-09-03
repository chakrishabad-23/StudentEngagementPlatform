admin.php                                                                                                                                                                                                                                                        <?php
error_reporting(E_ALL);
$host = "localhost";
$user = "root";
$password = "";
$db = "Exceed";
session_start();

$data = new mysqli($host, $user, $password, $db);

if ($data->connect_error) {
    die("Connection failed: " . $data->connect_error);
}

// Handle form submission for adding answers and images
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['question_id']) && isset($_POST['answer'])) {
    $question_id = $data->real_escape_string($_POST['question_id']);
    $answer = $data->real_escape_string($_POST['answer']);
    $image_path = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_name = basename($_FILES['image']['name']);
        $image_path = 'uploads/' . $image_name;
        
        // Ensure the uploads directory exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }
        
        if (move_uploaded_file($image_tmp_name, $image_path)) {
            $image_path = $data->real_escape_string($image_path);
        } else {
            $image_path = '';
        }
    }

    // Insert the answer and image path into the Answers table
    $insert_sql = "INSERT INTO Answers (question_id, answer, image_path) VALUES (?, ?, ?)";
    $stmt = $data->prepare($insert_sql);
    $stmt->bind_param("iss", $question_id, $answer, $image_path);

    if ($stmt->execute()) {
        echo "<script>alert('Answer submitted successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$question_sql = "SELECT Exceed1.id, Exceed1.question, Answers.answer, Answers.image_path 
                 FROM Exceed1 
                 LEFT JOIN Answers ON Exceed1.id = Answers.question_id 
                 ORDER BY Exceed1.id";
$question_result = $data->query($question_sql);

$question_data = [];
while ($row = $question_result->fetch_assoc()) {
    $question_data[$row['id']]['question'] = $row['question'];
    $question_data[$row['id']]['answers'][] = [
        'text' => $row['answer'],
        'image' => $row['image_path']
    ];
}

$data->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions and Answers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
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
            width: 100%;
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
        #answerSection {
            display: none;
            margin-top: 50px;
        }

        #answerInput {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        .image-upload-container {
            margin-top: 10px;
        }

        .image-upload-icon {
            cursor: pointer;
            font-size: 24px;
            color: #007bff;
            margin-right: 10px;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 100%;
            max-height: 200px;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        .no-answer {
            color: #888;
        }
        .iare-image {
            width: 100%;
            object-fit: cover;
            border-radius: 20px;
        }
    </style>
    <script>
        function showAnswerSection(questionId, questionText) {
            const escapedQuestionText = questionText ? questionText.replace(/'/g, "\\'") : '';
            document.getElementById("answerSection").style.display = "block";
            document.getElementById("questionDisplay").innerText = escapedQuestionText;
            document.getElementById("question_id").value = questionId;
            document.getElementById("answerInput").scrollIntoView({behavior: "smooth"});
        }

        function previewImage() {
            const fileInput = document.getElementById('imageInput');
            const imagePreview = document.getElementById('imagePreview');
            const file = fileInput.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        }

        function submitAnswer(event) {
            event.preventDefault();
            const form = event.target;
            const questionId = document.getElementById("question_id").value;
            const answerInput = document.getElementById("answerInput").value;

            if (answerInput.trim() === '') {
                alert('Please enter an answer before submitting.');
                return;
            }

            alert('Answer submitted successfully!');
            form.submit();
        }
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top p-2">
            <div class="container">
                <a class="navbar-brand " href="#">
                    <img src="https://media.licdn.com/dms/image/C4D0BAQGny_rcc2klag/company-logo_200_200/0/1631364840498?e=2147483647&v=beta&t=atkjcpbcro-0VwaPA2mqvLc7xcyuYkf5RITm4fAKtTo" class="sep" />
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav ml-auto">
                        <a class="nav-link active" id="navItem1" href="#wcuSection">
                            <i class="fas fa-home"></i> HOME
                            <span class="sr-only">(current)</span>
                        </a>
                        <a class="nav-link" href="#exploreMenuSection" id="navItem2">
                            <i class="fas fa-book"></i> NOTE
                        </a>
                        <a class="nav-link" href="#deliveryPaymentSection" id="navItem3">
                            <i class="fas fa-bell"></i> SUBSCRIBE
                        </a>
                        <a class="nav-link" href="#followUsSection" id="navItem4">
                            <i class="fas fa-users"></i> TEAM
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <h1 class="mt-5 pt-5">Questions and Answers</h1>
    <table border="2px" style="text-align: center;">
        <tr>
            <th>Questions</th>
            <th>Answers</th>
            <th>Add Answer</th>
        </tr>
        <?php foreach ($question_data as $question_id => $question): ?>
            <tr>
                <td><?php echo htmlspecialchars($question['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php 
                    if (!empty($question['answers'])) {
                        foreach ($question['answers'] as $answer) {
                            echo "<div>" . nl2br(htmlspecialchars($answer['text'] ?? 'No answer yet', ENT_QUOTES, 'UTF-8')) . "</div>";
                            if ($answer['image']) {
                                echo "<img src='" . htmlspecialchars($answer['image'], ENT_QUOTES, 'UTF-8') . "' class='image-preview iare-image' />";
                            }
                        }
                    } else {
                        echo "<div class='no-answer'>No answer yet</div>";
                    }
                    ?>
                </td>
                <td>
                    <button type="button" onclick="showAnswerSection(
                        '<?php echo $question_id; ?>',
                        '<?php echo htmlspecialchars($question['question'] ?? '', ENT_QUOTES, 'UTF-8'); ?>'
                    )">Add Answer</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div id="answerSection">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="shadow main-card1 mb-5 pt-3 pl-5 pr-5">
                    <h2 class="heading3">Answer the Question</h2>
                    <p id="questionDisplay" style="font-size: larger;"></p>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" onsubmit="submitAnswer(event)">
                        <input type="hidden" id="question_id" name="question_id">
                        <textarea id="answerInput" name="answer" rows="4" placeholder="Enter your answer here..."></textarea>
                        <div class="image-upload-container">
                            <input type="file" id="imageInput" name="image" accept="image/*" onchange="previewImage()">
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        </div>
                        <br><br>
                        <div class="text-card mb-5">
                            <button type="submit">Submit Answer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
