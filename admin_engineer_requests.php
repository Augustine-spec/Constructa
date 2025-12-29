<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit();
}
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Requests - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f6f7f2;
            --text-dark: #121212;
            --text-gray: #555555;
            --primary-green: #294033;
            --accent-green: #3d5a49;
            --card-bg: #ffffff;
            --warning-yellow: #f59e0b;
            --success-green: #16a34a;
            --error-red: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        header {
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-green);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary-green);
        }

        /* Main Content */
        main {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 3rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .page-subtitle {
            color: var(--text-gray);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-gray);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-value.pending {
            color: var(--warning-yellow);
        }

        .stat-value.approved {
            color: var(--success-green);
        }

        .stat-value.rejected {
            color: var(--error-red);
        }

        .requests-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .requests-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .requests-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-gray);
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .filter-tab.active {
            color: var(--primary-green);
            border-bottom-color: var(--primary-green);
        }

        .request-card {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }

        .request-card:hover {
            background-color: #f9fafb;
        }

        .request-card:last-child {
            border-bottom: none;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .request-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .request-email {
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-yellow);
        }

        .status-badge.approved {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--success-green);
        }

        .status-badge.rejected {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--error-red);
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
        }

        .request-bio {
            background-color: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .request-bio p {
            color: var(--text-gray);
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .request-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-approve {
            background-color: var(--success-green);
            color: white;
        }

        .btn-approve:hover {
            background-color: #15803d;
        }

        .btn-reject {
            background-color: var(--error-red);
            color: white;
        }

        .btn-reject:hover {
            background-color: #b91c1c;
        }

        .btn-view {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-view:hover {
            background-color: var(--accent-green);
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: var(--text-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        #loading {
            padding: 2rem;
            text-align: center;
        }

        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-green);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <header>
        <a href="admin_dashboard.php" class="logo">
            <i class="far fa-building"></i>
            Constructa Admin
        </a>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>

    <main>
        <div class="page-header">
            <h1 class="page-title">Engineer Requests</h1>
            <p class="page-subtitle">Review and manage engineer applications</p>
        </div>

        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-label">Pending Requests</div>
                <div class="stat-value pending" id="pendingCount">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Approved Engineers</div>
                <div class="stat-value approved" id="approvedCount">-</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Rejected Applications</div>
                <div class="stat-value rejected" id="rejectedCount">-</div>
            </div>
        </div>

        <div class="requests-container">
            <div class="requests-header">
                <h2 class="requests-title">Applications</h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" onclick="filterRequests('all')">All</button>
                    <button class="filter-tab" data-filter="pending" onclick="filterRequests('pending')">Pending</button>
                    <button class="filter-tab" data-filter="approved" onclick="filterRequests('approved')">Approved</button>
                    <button class="filter-tab" data-filter="rejected" onclick="filterRequests('rejected')">Rejected</button>
                </div>
            </div>

            <div id="loading">
                <div class="spinner"></div>
                <p>Loading engineer requests...</p>
            </div>

            <div id="requestsList" style="display: none;"></div>
        </div>
    </main>

    <script>
        let allRequests = [];
        let currentFilter = 'all';

        async function loadRequests() {
            try {
                const response = await fetch('backend/get_engineer_requests.php');
                const data = await response.json();

                if (data.success) {
                    allRequests = data.requests;
                    updateStats(data.stats);
                    displayRequests();
                } else {
                    document.getElementById('loading').innerHTML = '<p style="color: var(--error-red);">' + (data.message || 'Failed to load requests') + '</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loading').innerHTML = '<p style="color: var(--error-red);">An error occurred while loading requests.</p>';
            }
        }

        function updateStats(stats) {
            document.getElementById('pendingCount').textContent = stats.pending || 0;
            document.getElementById('approvedCount').textContent = stats.approved || 0;
            document.getElementById('rejectedCount').textContent = stats.rejected || 0;
        }

        function displayRequests() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('requestsList').style.display = 'block';

            const filteredRequests = currentFilter === 'all' 
                ? allRequests 
                : allRequests.filter(r => r.status === currentFilter);

            const requestsList = document.getElementById('requestsList');

            if (filteredRequests.length === 0) {
                requestsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No ${currentFilter === 'all' ? '' : currentFilter} requests found</p>
                    </div>
                `;
                return;
            }

            requestsList.innerHTML = filteredRequests.map(request => `
                <div class="request-card">
                    <div class="request-header">
                        <div class="request-info">
                            <h3>${request.name}</h3>
                            <p class="request-email">${request.email}</p>
                        </div>
                        <span class="status-badge ${request.status}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                    </div>

                    <div class="request-details">
                        <div class="detail-item">
                            <span class="detail-label">Specialization</span>
                            <span class="detail-value">${request.specialization || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Experience</span>
                            <span class="detail-value">${request.experience || 0} years</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">${request.phone || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Applied</span>
                            <span class="detail-value">${new Date(request.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>

                    ${request.bio ? `
                        <div class="request-bio">
                            <p><strong>Bio:</strong> ${request.bio}</p>
                        </div>
                    ` : ''}

                    ${request.portfolio_url ? `
                        <div class="request-bio">
                            <p><strong>Portfolio:</strong> <a href="${request.portfolio_url}" target="_blank">${request.portfolio_url}</a></p>
                        </div>
                    ` : ''}

                    <div class="request-actions">
                        ${request.status === 'pending' ? `
                            <button class="btn btn-approve" onclick="updateStatus(${request.id}, 'approved')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-reject" onclick="updateStatus(${request.id}, 'rejected')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        ` : request.status === 'approved' ? `
                            <button class="btn btn-reject" onclick="updateStatus(${request.id}, 'rejected')">
                                <i class="fas fa-times"></i> Revoke Approval
                            </button>
                        ` : `
                            <button class="btn btn-approve" onclick="updateStatus(${request.id}, 'approved')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        `}
                    </div>
                </div>
            `).join('');
        }

        async function updateStatus(userId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus === 'approved' ? 'approve' : 'reject'} this application?`)) {
                return;
            }

            try {
                const response = await fetch('backend/update_engineer_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        status: newStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Application ${newStatus} successfully!`);
                    loadRequests(); // Reload the list
                } else {
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the status.');
            }
        }

        function filterRequests(filter) {
            currentFilter = filter;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.dataset.filter === filter) {
                    tab.classList.add('active');
                }
            });

            displayRequests();
        }

        // Load requests on page load
        loadRequests();

        // Auto-refresh every 30 seconds
        setInterval(loadRequests, 30000);
    </script>
</body>

</html>
