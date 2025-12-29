<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Estimates - Constructa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #f6f7f2; --text-dark: #121212; --text-gray: #555555; --primary-green: #294033; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-color); min-height: 100vh; display: flex; flex-direction: column; }
        header { padding: 1.5rem 3rem; display: flex; justify-content: space-between; max-width: 1600px; margin: 0 auto; width: 100%; background: rgba(246, 247, 242, 0.9); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--primary-green); text-decoration: none; }
        nav { display: flex; gap: 2rem; }
        nav a { text-decoration: none; color: var(--text-dark); font-weight: 500; }
        main { flex: 1; max-width: 1400px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .estimates-table { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #294033, #3d5a49); color: white; }
        th { padding: 1.2rem; text-align: left; font-weight: 600; }
        td { padding: 1.2rem; border-bottom: 1px solid #f3f4f6; }
        tr:hover { background: #f9fafb; }
        .status { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .amount { font-size: 1.2rem; font-weight: 700; color: var(--primary-green); }
        .btn-action { padding: 0.5rem 1rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; margin-right: 0.5rem; }
        .btn-view { background: #e5e7eb; color: var(--text-dark); }
    </style>
</head>
<body>
    <header>
        <a href="engineer.php" class="logo"><i class="far fa-building"></i> Constructa</a>
        <nav>
            <a href="engineer.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="login.html">Logout</a>
        </nav>
    </header>
    <main>
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-file-invoice-dollar"></i> Active Estimates</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Monitor the status of your submitted project quotes and bids</p>
        </div>

        <div class="estimates-table">
            <table>
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Client</th>
                        <th>Submitted</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Modern Villa Construction</strong></td>
                        <td>John Davidson</td>
                        <td>Jan 10, 2025</td>
                        <td><span class="amount">$62,500</span></td>
                        <td><span class="status status-pending">Pending Review</span></td>
                        <td><button class="btn-action btn-view"><i class="fas fa-eye"></i> View</button></td>
                    </tr>
                    <tr>
                        <td><strong>Office Complex Renovation</strong></td>
                        <td>Sarah Martinez</td>
                        <td>Jan 8, 2025</td>
                        <td><span class="amount">$42,000</span></td>
                        <td><span class="status status-accepted">Accepted</span></td>
                        <td><button class="btn-action btn-view"><i class="fas fa-eye"></i> View</button></td>
                    </tr>
                    <tr>
                        <td><strong>Residential Complex Foundation</strong></td>
                        <td>Robert Chen</td>
                        <td>Jan 5, 2025</td>
                        <td><span class="amount">$68,000</span></td>
                        <td><span class="status status-pending">Pending Review</span></td>
                        <td><button class="btn-action btn-view"><i class="fas fa-eye"></i> View</button></td>
                    </tr>
                    <tr>
                        <td><strong>Eco-Friendly Home Design</strong></td>
                        <td>Linda Patel</td>
                        <td>Dec 28, 2024</td>
                        <td><span class="amount">$28,500</span></td>
                        <td><span class="status status-rejected">Declined</span></td>
                        <td><button class="btn-action btn-view"><i class="fas fa-eye"></i> View</button></td>
                    </tr>
                    <tr>
                        <td><strong>Industrial Warehouse Expansion</strong></td>
                        <td>Michael Thompson</td>
                        <td>Dec 22, 2024</td>
                        <td><span class="amount">$95,000</span></td>
                        <td><span class="status status-accepted">Accepted</span></td>
                        <td><button class="btn-action btn-view"><i class="fas fa-eye"></i> View</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
