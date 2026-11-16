<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/validation.php';

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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Collect form data
    $formData['name'] = isset($_POST['name']) ? $_POST['name'] : '';
    $formData['phone'] = isset($_POST['phone']) ? $_POST['phone'] : '';
    $formData['shifts'] = isset($_POST['shifts']) ? $_POST['shifts'] : '';
    $formData['sub'] = isset($_POST['sub']) ? $_POST['sub'] : '';
    $formData['reason'] = isset($_POST['reason']) ? $_POST['reason'] : '';

    // Validate via shared validation (field => message)
    $errors = validateRequest($formData);

    // If no errors, insert into database
    if (empty($errors)) {
        $db = getDb();
        insertRequest($db, $formData);
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

	<?php include __DIR__ . '/../src/nav.php'; ?>

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
				  <?php foreach (ALLOWED_REASONS as $reasonOption): ?>
				  <option value="<?php echo htmlspecialchars($reasonOption); ?>"<?php if ($formData['reason'] === $reasonOption) echo ' selected'; ?>><?php echo htmlspecialchars($reasonOption); ?></option>
				  <?php endforeach; ?>
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
