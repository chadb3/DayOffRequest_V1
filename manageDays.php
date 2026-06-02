<?php
require_once 'db.php';

// Handle approve/deny/archive POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'];

    if ($action === 'approve' || $action === 'deny') {
        $newStatus = ($action === 'approve') ? 'Approved' : 'Denied';
        $db = getDb();
        $stmt = $db->prepare("UPDATE EMPDAYOFFREQ SET STATUS = :status WHERE NUM = :id");
        $stmt->bindValue(':status', $newStatus, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        header('Location: manageDays.php');
        exit;
    }

    if ($action === 'archive') {
        $db = getDb();
        $db->exec('BEGIN TRANSACTION');
        try {
            // Insert into archive
            $stmt = $db->prepare("INSERT INTO EMPDAYOFFREQ_ARCHIVE (NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE) SELECT NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE FROM EMPDAYOFFREQ WHERE NUM = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            // Delete from active table
            $stmt = $db->prepare("DELETE FROM EMPDAYOFFREQ WHERE NUM = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            $db->exec('COMMIT');
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $archiveError = "Failed to archive request. The record has been retained.";
        }
        $db->close();

        if (!isset($archiveError)) {
            header('Location: manageDays.php?view=history');
            exit;
        }
    }
}

$view = isset($_GET['view']) ? $_GET['view'] : 'pending';

// Default to pending view
if ($view !== 'history') {
    $view = 'pending';
}

$db = getDb();

if ($view === 'history') {
    // Query approved/denied requests for history view
    $stmt = $db->prepare("SELECT NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE FROM EMPDAYOFFREQ WHERE STATUS IN (:status1, :status2)");
    $stmt->bindValue(':status1', 'Approved', SQLITE3_TEXT);
    $stmt->bindValue(':status2', 'Denied', SQLITE3_TEXT);
} else {
    // Query pending requests
    $stmt = $db->prepare("SELECT NUM, NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE FROM EMPDAYOFFREQ WHERE STATUS = :status");
    $stmt->bindValue(':status', 'Pending', SQLITE3_TEXT);
}

$result = $stmt->execute();

$requests = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $requests[] = $row;
}
$db->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Days Off</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

    <h1 id="head">Manage Days Off</h1>

    <?php include 'nav.php'; ?>

<div id="body">

    <div class="view-toggle">
        <a href="manageDays.php" class="<?php echo ($view === 'pending') ? 'active' : ''; ?>">Pending</a> |
        <a href="manageDays.php?view=history" class="<?php echo ($view === 'history') ? 'active' : ''; ?>">History</a>
    </div>

    <?php if (isset($archiveError)): ?>
        <p class="error"><?php echo htmlspecialchars($archiveError); ?></p>
    <?php endif; ?>

<?php if ($view === 'history'): ?>

    <h2>Request History</h2>

    <?php if (empty($requests)): ?>
        <p>No history records found.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Shifts</th>
                    <th>Substitute</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['NAME']); ?></td>
                    <td><?php echo htmlspecialchars($row['PHONE']); ?></td>
                    <td><?php echo htmlspecialchars($row['SHIFTS']); ?></td>
                    <td><?php echo htmlspecialchars($row['SUB']); ?></td>
                    <td><?php echo htmlspecialchars($row['REASON']); ?></td>
                    <td><?php echo htmlspecialchars($row['STATUS']); ?></td>
                    <td><?php echo htmlspecialchars($row['SUBMITTED_DATE']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['NUM']); ?>">
                            <input type="hidden" name="action" value="archive">
                            <input type="submit" value="Archive">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php else: ?>

    <h2>Pending Requests</h2>

    <?php if (empty($requests)): ?>
        <p>No pending requests.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Shifts</th>
                    <th>Substitute</th>
                    <th>Reason</th>
                    <th>Submitted Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['NAME']); ?></td>
                    <td><?php echo htmlspecialchars($row['PHONE']); ?></td>
                    <td><?php echo htmlspecialchars($row['SHIFTS']); ?></td>
                    <td><?php echo htmlspecialchars($row['SUB']); ?></td>
                    <td><?php echo htmlspecialchars($row['REASON']); ?></td>
                    <td><?php echo htmlspecialchars($row['SUBMITTED_DATE']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['NUM']); ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="submit" value="Approve">
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['NUM']); ?>">
                            <input type="hidden" name="action" value="deny">
                            <input type="submit" value="Deny">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>

</div>

<div id="bottom_div">
<p>Day off requester web-software</p>
</div>

</body>
</html>
