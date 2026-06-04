<?php
// Start the session
session_start();

ini_set('session.gc_maxlifetime', 30);
ini_set('session.cookie_lifetime', 30);

// Check if the session is set and if it is expired
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 30)) {
    session_unset();     // Unset $_SESSION variable for the run-time
    session_destroy();   // Destroy session data in storage
    header('location:logout.php'); // Redirect to login page
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Check if the user is logged in
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}
?>


<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> Equipment management System </title>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> Font Awesome CSS -->
    <link rel="shortcut icon" href="assets/images/fav.jpg">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawsom-all.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css" />
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<style>
    .logout-icon {
        width: 20px;
        /* Small icon size */
        height: 20px;
        /* Small icon size */
        fill: #f44336;
        /* Icon color */
        cursor: pointer;
    }
    
    .change-password-icon {
        width: 20px;
        /* Icon size */
        height: 20px;
        /* Icon size */
        fill: #4caf50;
        /* Icon color */
        cursor: pointer;
    }
</style>

<body>

    <!-- ################# Header Starts Here#######################--->

    <header id="menu-jk">

        <div id="nav-head" class="header-nav">
            <div class="container-fluid">
                <div class="row" style="background-color:pink;border-radius:20% ;height:100px">

                    <div class="col-lg-3"> <br><div id="currentDateTime"></div>
                    
                    
                       

                    </div>
                    
                        <div class="col-lg-7 col-md-3  col-sm-12" style="color:#000;font-weight:bold; font-size:30px; margin-top: 1% !important;">
                            <div class="row" style="height: 15px;"></div>
                            <div class="row" style="height:40px ;margin-left:70px">EQUIPMENT MANAGEMENT SYSTEM</div>
                            <a data-toggle="collapse" data-target="#menu" href="#menu"><i class="fas d-block d-md-none small-menu fa-bars"></i></a>
                        </div>

                        <div class="col-sm-1 d-none d-lg-block appoint">
                            <div class="row" style="height:20px"> </div>

                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="userIcon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-user"></i>
                            </button>
                                <div class="dropdown-menu" aria-labelledby="userIcon">


                                    <a class="dropdown-item" href="change_password.php"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="change-password-icon">
                                        <path d="M12 1a4 4 0 00-4 4v2H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-2V5a4 4 0 00-4-4zm0 2a2 2 0 012 2v2H10V5a2 2 0 012-2zm-1 16a3 3 0 100-6 3 3 0 000 6zm6-1v-3h-2v3h-2v-3h-2v3h-2v-5a4 4 0 118 0v5z" />
                                    </svg>Change Password</a>
                                    <a class="dropdown-item" href="logout.php"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="logout-icon">
                                        <path d="M15 17l-3-3h2V10h2v4h2l-3 3zm5-13H6a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z" />
                                    </svg>Logout</a>
                                </div>
                            </div>
                        
                    </div>
                    <!-- </div> -->

                </div>
            </div>
        </div>
    </header>


    <script src="https://cdn.jsdelivr.net/npm/nepali-date-converter/dist/nepali-date-converter.umd.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="assets/js/main.js"></script>
        <script src="assets/js/form-elements.js"></script>

        <!-- Include necessary scripts -->
        <script>
            // Initialize JavaScript functions
            $(document).ready(function() {
                updateDateTime();
                setInterval(updateDateTime, 1000);
            });

            function updateDateTime() {
                let a = new NepaliDate();
                NepaliDate.language = 'np';
                a.format('ddd DD, MMMM YYYY');
                var currentDateTime = new Date().toLocaleString("en-GB", {
                    timeZone: 'Asia/Kathmandu'
                });
                document.getElementById('currentDateTime').innerHTML = String(a) + ", " + currentDateTime;
            }
        </script>
        <!-- End: MAIN JAVASCRIPTS -->
