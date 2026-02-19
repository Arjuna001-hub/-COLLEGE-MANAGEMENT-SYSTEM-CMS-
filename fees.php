<?php
// student/fees.php
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('student')) {
    redirect('../login.php');
}

$page_title = 'My Fees';
$user_id = $_SESSION['user_id'];
$student = getStudentByUserId($user_id);

// Get fee records
$fees = getStudentFees($student['id']);

// Calculate totals
$total_fees = getTotalFees($student['id']);
$paid_fees = getPaidFees($student['id']);
$pending_fees = $total_fees - $paid_fees;

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
            <h4 class="mb-0">My Fees</h4>
            <small class="text-muted">View and manage your fees</small>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Fees</h5>
                    <h2 class="mb-0">$<?php echo number_format($total_fees, 2); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Paid Fees</h5>
                    <h2 class="mb-0">$<?php echo number_format($paid_fees, 2); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-<?php echo $pending_fees > 0 ? 'danger' : 'secondary'; ?>">
                <div class="card-body">
                    <h5 class="card-title">Pending Fees</h5>
                    <h2 class="mb-0">$<?php echo number_format($pending_fees, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Progress -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Payment Progress</h5>
            <?php 
            $paid_percentage = $total_fees > 0 ? ($paid_fees / $total_fees) * 100 : 0;
            ?>
            <div class="progress mb-2" style="height: 25px;">
                <div class="progress-bar bg-success" style="width: <?php echo $paid_percentage; ?>%">
                    $<?php echo number_format($paid_fees, 2); ?> paid
                </div>
                <div class="progress-bar bg-danger" style="width: <?php echo 100 - $paid_percentage; ?>%">
                    $<?php echo number_format($pending_fees, 2); ?> pending
                </div>
            </div>
            <small class="text-muted"><?php echo round($paid_percentage, 2); ?>% of total fees paid</small>
        </div>
    </div>
    
    <!-- Fee Records -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Fee Records</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fees as $fee): ?>
                        <tr>
                            <td><?php echo ucfirst($fee['fee_type']); ?></td>
                            <td>$<?php echo number_format($fee['amount'], 2); ?></td>
                            <td>
                                <?php echo formatDate($fee['due_date']); ?>
                                <?php if(strtotime($fee['due_date']) < time() && $fee['status'] == 'pending'): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $fee['status'] == 'paid' ? 'success' : 'warning'; 
                                ?>">
                                    <?php echo ucfirst($fee['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $fee['paid_date'] ? formatDate($fee['paid_date']) : '-'; ?></td>
                            <td>
                                <?php if($fee['status'] == 'paid'): ?>
                                    <a href="download_receipt.php?id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-download"></i> Receipt
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods -->
    <?php if($pending_fees > 0): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Make a Payment</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                            <h6>Credit Card</h6>
                            <p class="text-muted small">Pay via Visa, MasterCard</p>
                            <button class="btn btn-sm btn-outline-primary" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-university fa-3x text-success mb-3"></i>
                            <h6>Bank Transfer</h6>
                            <p class="text-muted small">Direct bank transfer</p>
                            <button class="btn btn-sm btn-outline-success" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-mobile-alt fa-3x text-warning mb-3"></i>
                            <h6>Mobile Payment</h6>
                            <p class="text-muted small">Pay via mobile wallet</p>
                            <button class="btn btn-sm btn-outline-warning" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> Online payment system is currently under development. 
                Please visit the accounts office for payment.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>