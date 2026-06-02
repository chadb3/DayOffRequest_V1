<?php
require_once 'db.php';

$errors = [];
$success = false;

// Preserve submitted values for re-display on validation failure
$formData = [
    'name' => '',
    'phone' => '',
    'shifts' => '',
    'sub' => '',
    'reason' => ''
];

$allowedReasons = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $formData['name'] = isset($_POST['name']) ? $_POST['name'] : '';
    $formData['phone'] = isset($_POST['phone']) ? $_POST['phone'] : '';
    $formData['shifts'] = isset($_POST['shifts']) ? $_POST['shifts'] : '';
    $formData['sub'] = isset($_POST['sub']) ? $_POST['sub'] : '';
    $formData['reason'] = isset($_POST['reason']) ? $_POST['reason'] : '';

    // Validate required fields are non-empty and not whitespace-only
    if (trim($formData['name']) === '') {
        $errors[] = 'Name is required.';
    }
    if (trim($formData['phone']) === '') {
        $errors[] = 'Phone is required.';
    }
    if (trim($formData['shifts']) === '') {
        $errors[] = 'Shifts is required.';
    }
    if (trim($formData['sub']) === '') {
        $errors[] = 'Substitute is required.';
    }
    if (trim($formData['reason']) === '') {
        $errors[] = 'Reason is required.';
    }

    // Validate reason is from the allowed set
    if (!in_array($formData['reason'], $allowedReasons, true)) {
        $errors[] = 'Invalid reason selected.';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $db = getDb();
        $stmt = $db->prepare("INSERT INTO EMPDAYOFFREQ (NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE) VALUES (:name, :phone, :shifts, :sub, :reason, datetime('now','localtime'))");
        $stmt->bindValue(':name', $formData['name'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $formData['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':shifts', $formData['shifts'], SQLITE3_TEXT);
        $stmt->bindValue(':sub', $formData['sub'], SQLITE3_TEXT);
        $stmt->bindValue(':reason', $formData['reason'], SQLITE3_TEXT);
        $stmt->execute();
        $db->close();
        $success = true;

        // Clear form data after successful submission
        $formData = [
            'name' => '',
            'phone' => '',
            'shifts' => '',
            'sub' => '',
            'reason' => ''
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Day off request!</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

	<h1 id="head">Day off request form!</h1>

	<?php include 'nav.php'; ?>

<!--Body of the html has all the form actions and button!-->
<div id="body">

	<?php if ($success): ?>
		<p style="color: green; font-weight: bold;">Your day-off request has been submitted successfully!</p>
	<?php endif; ?>

	<?php if (!empty($errors)): ?>
		<div style="color: red;">
			<ul>
			<?php foreach ($errors as $error): ?>
				<li><?php echo htmlspecialchars($error); ?></li>
			<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="POST">
		Name:<input type="text" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>"><br>
		Phone:<input type="text" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>"><br>
		Shifts:<input type="text" name="shifts" value="<?php echo htmlspecialchars($formData['shifts']); ?>"><br>
		Sub:<input type="text" name="sub" value="<?php echo htmlspecialchars($formData['sub']); ?>"><br>
		Reason:<select name="reason">
				  <option value="Medical"<?php if ($formData['reason'] === 'Medical') echo ' selected'; ?>>Medical</option>
				  <option value="Dental"<?php if ($formData['reason'] === 'Dental') echo ' selected'; ?>>Dental</option>
				  <option value="Family Emergency"<?php if ($formData['reason'] === 'Family Emergency') echo ' selected'; ?>>Family Emergency</option>
				  <option value="Personal"<?php if ($formData['reason'] === 'Personal') echo ' selected'; ?>>Personal</option>
				  <option value="Other"<?php if ($formData['reason'] === 'Other') echo ' selected'; ?>>Other</option>
			  </select><br>
		<input type="submit" name="submit" value="Send">
	</form>

</div>
<!--Bottom div with information -->
<div id="bottom_div">
<p>Day off requester web-software</p>
</div>

</body>
</html>
