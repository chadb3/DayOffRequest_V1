<?php
require_once __DIR__ . '/../src/db.php';

// Handle approve/deny/archive POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $action = (string) $_POST['action'];
    $id = (int) $_POST['id'];

    if ($action === 'approve' || $action === 'deny') {
        $newStatus = ($action === 'approve') ? 'Approved' : 'Denied';
        $db = getDb();
        updateRequestStatus($db, $id, $newStatus);
        $db->close();
        header('Location: manageDays.php');
        exit;
    }

    if ($action === 'archive') {
        $db = getDb();
        $archived = archiveRequest($db, $id);
        $db->close();

        if ($archived) {
            header('Location: manageDays.php?view=history');
            exit;
        }
        $archiveError = "Failed to archive request. The record has been retained.";
    }
}

$view = isset($_GET['view']) ? $_GET['view'] : 'pending';

// Default to pending view
if ($view !== 'history') {
    $view = 'pending';
}

$db = getDb();

$requests = ($view === 'history') ? getHistoryRequests($db) : getPendingRequests($db);
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

    <?php include __DIR__ . '/../src/nav.php'; ?>

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
