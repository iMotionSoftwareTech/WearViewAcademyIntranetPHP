<!DOCTYPE php>
<php lang="en">
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
            <img src="img/school.avif" alt="School">
            <div class="hero-overlay">
                <h2>Welcome to the Staff Intranet</h2>
                <p>Your central hub for school resources, announcements and support.</p>
            </div>
        </section>
        <section class="quick-links">
            <h2>Quick Links</h2>
            <div class="cards">
                <a class="card" href="#">
                    <i class="fa-solid fa-desktop blue"></i>
                    <p>IT Support</p>
                </a>
                <a class="card" href="#">
                    <i class="fa-regular fa-calendar green"></i>
                    <p>Timetable</p>
                </a>
                <a class="card" href="#">
                    <i class="fa-solid fa-users purple"></i>
                    <p>Staff Directory</p>
                </a>
                <a class="card" href="#">
                    <i class="fa-regular fa-file-lines orange"></i>
                    <p>Policies</p>
                </a>
                <a class="card" href="#">
                    <i class="fa-regular fa-calendar-days red"></i>
                    <p>School Calendar</p>
                </a>
                <a class="card" href="#">
                    <i class="fa-solid fa-book-open teal"></i>
                    <p>Teaching Resources</p>
                </a>
            </div>
        </section>
        <section class="announcements">
            <h2>Announcements</h2>
            <div class="announcement blue">
                <h3>Staff Meeting — Monday 14 July</h3>
                <p>Whole-staff briefing at 8:15am in the Main Hall. Please be prompt.</p>
            </div>
            <div class="announcement orange">
                <h3>Internet Maintenance — Wednesday Evening</h3>
                <p>Network maintenance scheduled 6pm–8pm Wednesday. Wi-Fi may be intermittent.</p>
            </div>
            <div class="announcement green">
                <h3>Sports Day Volunteers Needed</h3>
                <p>Please sign up in the staffroom if you can help on Friday 18 July.</p>
            </div>
        </section>
    </main>    
    <?php include 'footer.php'; ?> 
</body>
</php>