<?php
// admin/announcements.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$page_title = 'Announcements';
$message = '';
$error = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($conn->query("DELETE FROM announcements WHERE id = $id")) {
        $message = "Announcement deleted successfully!";
    } else {
        $error = "Error deleting announcement: " . $conn->error;
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $target_audience = sanitize($_POST['target_audience']);
    $priority = sanitize($_POST['priority']);
    $expiry_date = $_POST['expiry_date'];
    
    if ($action == 'add') {
        $sql = "INSERT INTO announcements (title, content, posted_by, target_audience, priority, expiry_date) 
                VALUES ('$title', '$content', {$_SESSION['user_id']}, '$target_audience', '$priority', '$expiry_date')";
        
        if ($conn->query($sql)) {
            $message = "Announcement posted successfully!";
        } else {
            $error = "Error posting announcement: " . $conn->error;
        }
    } elseif ($action == 'edit') {
        $sql = "UPDATE announcements SET 
                title = '$title',
                content = '$content',
                target_audience = '$target_audience',
                priority = '$priority',
                expiry_date = '$expiry_date'
                WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Announcement updated successfully!";
        } else {
            $error = "Error updating announcement: " . $conn->error;
        }
    }
}

// Get all announcements
$announcements = $conn->query("
    SELECT a.*, u.full_name as posted_by_name 
    FROM announcements a 
    JOIN users u ON a.posted_by = u.id 
    ORDER BY a.posted_date DESC
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <!-- Top Bar -->
    <div class="top-bar">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <div>
            <h4 class="mb-0">Announcements</h4>
            <small class="text-muted">Post and manage announcements</small>
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> New Announcement
            </button>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Announcements List -->
    <div class="row">
        <?php while($announcement = $announcements->fetch_assoc()): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100 <?php echo $announcement['priority'] == 'urgent' ? 'border-danger' : ''; ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?php echo $announcement['title']; ?></h5>
                        <small class="text-muted">
                            Posted by <?php echo $announcement['posted_by_name']; ?> • 
                            <?php echo timeAgo($announcement['posted_date']); ?>
                        </small>
                    </div>
                    <div>
                        <span class="badge bg-<?php 
                            echo $announcement['priority'] == 'urgent' ? 'danger' : 
                                ($announcement['priority'] == 'high' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo ucfirst($announcement['priority']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br($announcement['content']); ?></p>
                    
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-users"></i> Target: <?php echo ucfirst($announcement['target_audience']); ?>
                            <?php if($announcement['expiry_date']): ?>
                                • <i class="fas fa-clock"></i> Expires: <?php echo formatDate($announcement['expiry_date']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-warning" onclick="editAnnouncement(<?php echo $announcement['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add/Edit Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="announcementId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" id="content" rows="5" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Target Audience</label>
                            <select class="form-select" name="target_audience" id="targetAudience" required>
                                <option value="all">Everyone</option>
                                <option value="students">Students Only</option>
                                <option value="faculty">Faculty Only</option>
                                <option value="admin">Admin Only</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority" id="priority" required>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" id="expiryDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'New Announcement';
    document.getElementById('action').value = 'add';
    document.getElementById('announcementId').value = '';
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    document.getElementById('targetAudience').value = 'all';
    document.getElementById('priority').value = 'normal';
    document.getElementById('expiryDate').value = '';
    
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}

function editAnnouncement(id) {
    fetch('get_announcement.php?id=' + id)
        .then(response => response.json())
        .then(announcement => {
            document.getElementById('modalTitle').textContent = 'Edit Announcement';
            document.getElementById('action').value = 'edit';
            document.getElementById('announcementId').value = announcement.id;
            document.getElementById('title').value = announcement.title;
            document.getElementById('content').value = announcement.content;
            document.getElementById('targetAudience').value = announcement.target_audience;
            document.getElementById('priority').value = announcement.priority;
            document.getElementById('expiryDate').value = announcement.expiry_date;
            
            new bootstrap.Modal(document.getElementById('announcementModal')).show();
        });
}

function deleteAnnouncement(id) {
    if (confirm('Are you sure you want to delete this announcement?')) {
        window.location.href = 'announcements.php?delete=' + id;
    }
}
</script>

<?php include '../includes/footer.php'; ?>