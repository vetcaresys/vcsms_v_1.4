<?php
include '../../config.php';
session_start();

if (isset($_GET['form_type_id'])) {
    $form_type_id = $_GET['form_type_id'];

    $stmt = $pdo->prepare("SELECT * FROM form_questions WHERE form_type_id = ? ORDER BY question_id ASC");
    $stmt->execute([$form_type_id]);
    $questions = $stmt->fetchAll();

    foreach ($questions as $q) {
        $qid = $q['question_id'];
        $label = htmlspecialchars($q['question_text']);
        $type = $q['input_type'];
        $options = $q['options'] ? json_decode($q['options'], true) : [];

        echo "<div class='mb-3'><label class='form-label'>{$label}</label>";

        switch ($type) {
            case 'text':
                echo "<input type='text' name='q[{$qid}]' class='form-control'>";
                break;
            case 'textarea':
                echo "<textarea name='q[{$qid}]' class='form-control'></textarea>";
                break;
            case 'yesno':
                echo "
                <div class='form-check form-check-inline'>
                  <input class='form-check-input' type='radio' name='q[{$qid}]' value='Yes'>
                  <label class='form-check-label'>Yes</label>
                </div>
                <div class='form-check form-check-inline'>
                  <input class='form-check-input' type='radio' name='q[{$qid}]' value='No'>
                  <label class='form-check-label'>No</label>
                </div>";
                break;
            case 'select':
                echo "<select name='q[{$qid}]' class='form-select'>";
                echo "<option value=''>-- Select --</option>";
                foreach ($options as $opt) {
                    echo "<option value='{$opt}'>{$opt}</option>";
                }
                echo "</select>";
                break;
            case 'number':
                echo "<input type='number' name='q[{$qid}]' class='form-control'>";
                break;
            case 'date':
                echo "<input type='date' name='q[{$qid}]' class='form-control'>";
                break;
        }

        echo "</div>";
    }
}
?>
