<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule & Calendar - Constructa</title>
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
        
        .calendar-container { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .calendar { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .calendar-header h3 { font-size: 1.5rem; }
        .calendar-nav { display: flex; gap: 1rem; }
        .btn-nav { background: #e5e7eb; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .day-header { text-align: center; font-weight: 600; padding: 0.5rem; color: var(--text-gray); font-size: 0.85rem; }
        .day-cell { aspect-ratio: 1; border: 1px solid #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
        .day-cell:hover { background: #f9fafb; }
        .day-cell.today { background: linear-gradient(135deg, #294033, #3d5a49); color: white; font-weight: 700; }
        .day-cell.has-event { background: #dbeafe; color: #1e40af; font-weight: 600; }
        
        .events-sidebar { background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .events-sidebar h3 { margin-bottom: 1.5rem; }
        .event-item { padding: 1rem; border-left: 4px solid #3b82f6; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem; }
        .event-time { font-size: 0.85rem; color: var(--text-gray); margin-bottom: 0.3rem; }
        .event-title { font-weight: 600; margin-bottom: 0.3rem; }
        .event-location { font-size: 0.85rem; color: var(--text-gray); }
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
            <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Schedule & Calendar</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Keep track of project timelines, deadlines, and meetings</p>
        </div>

        <div class="calendar-container">
            <div class="calendar">
                <div class="calendar-header">
                    <h3>January 2025</h3>
                    <div class="calendar-nav">
                        <button class="btn-nav"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn-nav">Today</button>
                        <button class="btn-nav"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-grid">
                    <div class="day-header">Sun</div>
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                    <div class="day-header">Sat</div>
                    
                    <div class="day-cell"></div>
                    <div class="day-cell"></div>
                    <div class="day-cell"></div>
                    <div class="day-cell">1</div>
                    <div class="day-cell">2</div>
                    <div class="day-cell">3</div>
                    <div class="day-cell">4</div>
                    
                    <div class="day-cell">5</div>
                    <div class="day-cell">6</div>
                    <div class="day-cell">7</div>
                    <div class="day-cell has-event">8</div>
                    <div class="day-cell">9</div>
                    <div class="day-cell has-event">10</div>
                    <div class="day-cell">11</div>
                    
                    <div class="day-cell">12</div>
                    <div class="day-cell">13</div>
                    <div class="day-cell">14</div>
                    <div class="day-cell has-event">15</div>
                    <div class="day-cell">16</div>
                    <div class="day-cell">17</div>
                    <div class="day-cell">18</div>
                    
                    <div class="day-cell">19</div>
                    <div class="day-cell">20</div>
                    <div class="day-cell">21</div>
                    <div class="day-cell has-event">22</div>
                    <div class="day-cell">23</div>
                    <div class="day-cell">24</div>
                    <div class="day-cell">25</div>
                    
                    <div class="day-cell">26</div>
                    <div class="day-cell">27</div>
                    <div class="day-cell">28</div>
                    <div class="day-cell today">29</div>
                    <div class="day-cell">30</div>
                    <div class="day-cell">31</div>
                    <div class="day-cell"></div>
                </div>
            </div>

            <div class="events-sidebar">
                <h3>Upcoming Events</h3>
                
                <div class="event-item" style="border-color: #3b82f6;">
                    <div class="event-time"><i class="fas fa-clock"></i> Jan 29, 2025 - 10:00 AM</div>
                    <div class="event-title">Site Inspection - Sunset Villa</div>
                    <div class="event-location"><i class="fas fa-map-marker-alt"></i> San Francisco, CA</div>
                </div>

                <div class="event-item" style="border-color: #10b981;">
                    <div class="event-time"><i class="fas fa-clock"></i> Jan 30, 2025 - 2:00 PM</div>
                    <div class="event-title">Client Meeting - Office Complex</div>
                    <div class="event-location"><i class="fas fa-video"></i> Virtual Meeting</div>
                </div>

                <div class="event-item" style="border-color: #f59e0b;">
                    <div class="event-time"><i class="fas fa-clock"></i> Feb 1, 2025 - 9:00 AM</div>
                    <div class="event-title">Foundation Review</div>
                    <div class="event-location"><i class="fas fa-map-marker-alt"></i> Seattle, WA</div>
                </div>

                <div class="event-item" style="border-color: #ec4899;">
                    <div class="event-time"><i class="fas fa-clock"></i> Feb 3, 2025 - 11:00 AM</div>
                    <div class="event-title">Team Coordination Meeting</div>
                    <div class="event-location"><i class="fas fa-building"></i> Office</div>
                </div>

                <div class="event-item" style="border-color: #8b5cf6;">
                    <div class="event-time"><i class="fas fa-clock"></i> Feb 5, 2025 - 3:00 PM</div>
                    <div class="event-title">Project Deadline - Heritage Building</div>
                    <div class="event-location"><i class="fas fa-flag-checkered"></i> Milestone</div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
