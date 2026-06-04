<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>INVENTORY MANAGEMENT SYSTEM</title>

<link rel="shortcut icon" href="assets/images/chinar jimmy.png">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">

<style>
/* ===== BASE ===== */
html,body{height:100%;}
body{
    margin:0;
    display:flex;
    flex-direction:column;
    background:#f4f8ff;
    font-family:Segoe UI, Arial, sans-serif;
}

/* ===== HEADER ===== */
.header-area{
    background:linear-gradient(90deg,#0072ff,#00c6ff);
    height:140px;
    box-shadow:0 4px 15px rgba(0,0,0,.25);
    display:flex;
    align-items:center;
    padding:0 20px;          /* 🔥 edge control */
}

/* ===== HEADER FLEX LAYOUT ===== */
.header-inner{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:space-between;
    position:relative;
}

/* ===== TITLE ===== */
.header-title{
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    font-size:50px;
    font-weight:900;
    color:#ffeb3b;
    letter-spacing:2px;
    white-space:nowrap;
    text-transform:uppercase;
    text-shadow:
        1px 1px 0 #c9a800,
        2px 2px 0 #b89a00,
        3px 3px 0 #9e8600,
        4px 4px 6px rgba(0,0,0,.6);
}

/* ===== LOGOS ===== */
.logo-left{
    height:240px;        /* 🔥 LEFT BIG */
    width:auto;
    object-fit:contain;
    margin-top:48px;          /* 🔥 top aligned */
    margin-left:-130px;       /* 🔥 push left */
    padding:0;
}

.logo-right{
    height:160px;        /* RIGHT NORMAL */
    width:auto;
    object-fit:contain;
     margin-top:20px;
    
}

/* ===== LOGO ANIMATION ===== */
.logo-left,
.logo-right{
    animation:floatLogo 4s ease-in-out infinite,
              glowPulse 3s ease-in-out infinite;
}

@keyframes floatLogo{
    0%{transform:translateY(0);}
    50%{transform:translateY(-10px);}
    100%{transform:translateY(0);}
}
@keyframes glowPulse{
    0%{filter:drop-shadow(0 0 6px rgba(255,255,255,.6));}
    50%{filter:drop-shadow(0 0 18px rgba(255,255,255,.95));}
    100%{filter:drop-shadow(0 0 6px rgba(255,255,255,.6));}
}

/* ===== MAIN CONTENT ===== */
.main-content{
    flex:1;
    display:flex;
    align-items:center;
}
.slider-wrapper{
    background:#fff;
    padding:22px;
    border-radius:24px;
    box-shadow:0 20px 45px rgba(0,0,0,.2);
}
.carousel-item{height:420px;}
.carousel-item img{
    width:100%;
    height:100%;
    object-fit:contain;
}

/* ===== LOGIN ===== */
.login-btn img{
    height:90px;
    transition:.3s;
}
.login-btn img:hover{
    transform:scale(1.15);
}

/* ===== FOOTER ===== */
.footer{
    background:#004ea8;
    color:#fff;
    text-align:center;
    padding:18px;
    font-size:18px;
    font-weight:600;
}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .header-area{height:90px;}
    .header-title{font-size:20px;}
    .logo-left{height:110px;}
    .logo-right{height:70px;}
    .carousel-item{height:280px;}
}
</style>
</head>

<body>

<!-- ===== HEADER ===== -->
<div class="header-area">
    <div class="header-inner">

        <!-- LEFT LOGO -->
        <img src="photos/Chinnarjimmy.png"
             class="logo-left"
             alt="Left Logo">

        <!-- TITLE -->
        <div class="header-title">
            INVENTORY MANAGEMENT SYSTEM
        </div>

        <!-- RIGHT LOGO -->
        <img src="photos/chinar jimmy.png"
             class="logo-right"
             alt="Right Logo">

    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    <div class="container">
        <div class="slider-wrapper text-center">

            <div id="eqptSlider" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active"><img src="photo/s.avif"></div>
                    <div class="carousel-item"><img src="photo/ip.jpg"></div>
                    <div class="carousel-item"><img src="photo/otdr.jpeg"></div>
                    <div class="carousel-item"><img src="photo/1.jpeg"></div>
                </div>
            </div>

            <div class="mt-4">
                <a href="edit/au.php" class="login-btn">
                    <img src="photos/1.png">
                </a>
            </div>

        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<div class="footer">
    DESIGN AND DEVELOPED BY 15 CESR
</div>

<script src="assets/js/jquery-3.2.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
