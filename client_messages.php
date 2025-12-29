<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'engineer') { header('Location: login.html'); exit(); }
$username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Engineer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Messages - Constructa</title>
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
        main { flex: 1; max-width: 1200px; margin: 0 auto; width: 100%; padding: 3rem; }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-title { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(135deg, #294033, #3d5a49); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .messages-container { display: grid; grid-template-columns: 300px 1fr; gap: 2rem; height: 600px; }
        .contacts-list { background: white; border-radius: 20px; padding: 1.5rem; overflow-y: auto; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .contact-item { padding: 1rem; border-radius: 12px; cursor: pointer; margin-bottom: 0.5rem; transition: all 0.3s; display: flex; align-items: center; gap: 1rem; }
        .contact-item:hover, .contact-item.active { background: #f3f4f6; }
        .contact-avatar { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; }
        .contact-info h4 { font-size: 0.95rem; margin-bottom: 0.2rem; }
        .contact-info p { font-size: 0.8rem; color: var(--text-gray); }
        .chat-area { background: white; border-radius: 20px; display: flex; flex-direction: column; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .chat-header { padding: 1.5rem; border-bottom: 1px solid #f3f4f6; }
        .chat-messages { flex: 1; padding: 1.5rem; overflow-y: auto; }
        .message { margin-bottom: 1.5rem; display: flex; gap: 1rem; }
        .message.sent { flex-direction: row-reverse; }
        .message-bubble { max-width: 70%; padding: 1rem; border-radius: 16px; }
        .message.received .message-bubble { background: #f3f4f6; }
        .message.sent .message-bubble { background: linear-gradient(135deg, #294033, #3d5a49); color: white; }
        .message-time { font-size: 0.75rem; color: var(--text-gray); margin-top: 0.5rem; }
        .chat-input { padding: 1.5rem; border-top: 1px solid #f3f4f6; display: flex; gap: 1rem; }
        .chat-input input { flex: 1; padding: 0.8rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px; }
        .btn-send { background: linear-gradient(135deg, #294033, #3d5a49); color: white; padding: 0.8rem 1.5rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
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
            <h1 class="page-title"><i class="fas fa-comments"></i> Client Messages</h1>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Direct communication channel with your current clients</p>
        </div>

        <div class="messages-container">
            <div class="contacts-list">
                <div class="contact-item active">
                    <div class="contact-avatar">JD</div>
                    <div class="contact-info">
                        <h4>John Davidson</h4>
                        <p>Sunset Villa Project</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-avatar" style="background: linear-gradient(135deg, #ec4899, #db2777);">SM</div>
                    <div class="contact-info">
                        <h4>Sarah Martinez</h4>
                        <p>Office Complex</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">RC</div>
                    <div class="contact-info">
                        <h4>Robert Chen</h4>
                        <p>Green Homes Community</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">LP</div>
                    <div class="contact-info">
                        <h4>Linda Patel</h4>
                        <p>Heritage Building</p>
                    </div>
                </div>
            </div>

            <div class="chat-area">
                <div class="chat-header">
                    <h3>John Davidson</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem;">Sunset Villa Project</p>
                </div>
                <div class="chat-messages">
                    <div class="message received">
                        <div class="message-bubble">
                            Hi! I wanted to check on the progress of the foundation work. Is everything on schedule?
                            <div class="message-time">10:30 AM</div>
                        </div>
                    </div>
                    <div class="message sent">
                        <div class="message-bubble">
                            Hello John! Yes, the foundation work is progressing well. We're about 65% complete and right on schedule. The concrete curing is going as planned.
                            <div class="message-time" style="color: rgba(255,255,255,0.7);">10:35 AM</div>
                        </div>
                    </div>
                    <div class="message received">
                        <div class="message-bubble">
                            That's great to hear! When can we schedule the next site visit?
                            <div class="message-time">10:40 AM</div>
                        </div>
                    </div>
                    <div class="message sent">
                        <div class="message-bubble">
                            I'm available this Friday afternoon or Saturday morning. Which works better for you?
                            <div class="message-time" style="color: rgba(255,255,255,0.7);">10:42 AM</div>
                        </div>
                    </div>
                </div>
                <div class="chat-input">
                    <input type="text" placeholder="Type your message...">
                    <button class="btn-send"><i class="fas fa-paper-plane"></i> Send</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
