<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_applicant();

$asset_path = '../';
$page_title = 'My Messages';

$applicant = current_applicant($conn);
if (!$applicant) {
    redirect('../logout.php');
}

// Mark all as read when viewed
$conn->query('UPDATE messages SET is_read = 1 WHERE applicant_id = ' . (int) $applicant['id']);

$stmt = $conn->prepare('SELECT m.*, u.full_name AS sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.applicant_id = ? ORDER BY m.created_at DESC');
$stmt->bind_param('i', $applicant['id']);
$stmt->execute();
$messages = $stmt->get_result();

require __DIR__ . '/../includes/header.php';
?>

<section class="messages-section">
    <h1>My Messages</h1>
    <?php if ($messages->num_rows === 0): ?>
        <p>You have no messages yet.</p>
    <?php else: ?>
        <?php while ($msg = $messages->fetch_assoc()): ?>
            <div class="message-card">
                <div class="message-meta">
                    <strong><?php echo sanitize($msg['subject']); ?></strong>
                    <span class="muted"><?php echo sanitize($msg['sender_name']); ?> &middot; <?php echo date('d M Y, H:i', strtotime($msg['created_at'])); ?></span>
                </div>
                <p><?php echo nl2br(sanitize($msg['body'])); ?></p>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
