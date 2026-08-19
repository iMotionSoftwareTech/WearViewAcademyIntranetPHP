<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WearView Academy Intranet</title>
    <link rel="stylesheet" href="css/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/site.js"></script>
</head>
<body>
    <?php include 'header.php'; include 'navmenu.php'; ?> 
    <main>
        <section class="hero">
            <img src="img/itsupport.avif" alt="IT Support">
            <div class="hero-overlay">
                <h2>IT Support</h2>
                <p>Report faults, find quick fixes, and get help from the IT team. We aim to resolve issues as quickly as possible.</p>
            </div>
            <div class="overlay"></div>
        </section>
        <section class="quick-help">
            <h2>Quick Help Topics</h2>
            <div class="help-cards">
                <a class="help-card">
                    <i class="fa-solid fa-video blue"></i>
                    <p>Projector Faults</p>
                </a>
                <a class="help-card">
                    <i class="fa-solid fa-desktop green"></i>
                    <p>Classroom Computers</p>
                </a>
                <a class="help-card">
                    <i class="fa-solid fa-key orange"></i>
                    <p>Password Resets</p>
                </a>
                <a class="help-card">
                    <i class="fa-solid fa-print purple"></i>
                    <p>Printer Problems</p>
                </a>
                <a class="help-card">
                    <i class="fa-solid fa-wifi red"></i>
                    <p>Wi-Fi Issues</p>
                </a>
            </div>
        </section>
        <section class="support-info-section">
            <div class="support-team-card">
                <!-- Left Side: Icon & Contact Details Grouped Together -->
                <div class="support-details">
                    <i class="fa-solid fa-headphones support-icon"></i>
                    <div class="support-text">
                        <h3>IT Support Team</h3>
                        <p>itsupport@wearview.ac.uk</p>
                        <p>Ext. 221 or (0121) 555 0148</p>
                    </div>
                </div>
                
                <!-- Right Side: Availability Hours -->
                <div class="support-hours">
                    <p>Available Mon–Fri, 7:30am – 5:00pm</p>
                </div>
            </div>            
    </section>
    </main>
    <?php include 'footer.php'; ?> 
</body>
</html>