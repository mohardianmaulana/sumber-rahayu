<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Selamat Datang</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html {
            height: 100%;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: url('template/img/back.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-box {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 400px;
            padding: 40px;
            transform: translate(-50%,-50%);
            background: transparent; /* Changed to transparent */
            box-sizing: border-box;
            border-radius: 20px;
            backdrop-filter:blur(15px);
            border: 3px solid rgba(255,255,255,0.5); /* Optional border for visibility */
        }
        .login-box h2 {
            margin: 0 0 30px;
            padding: 0;
            color: #fff;
            text-align: center;
        }
        .login-box .user-box {
            position: relative;
        }
        .login-box .user-box input {
            width: 100%;
            padding: 10px 0; 
            font-size: 16px;
            color: #fff;
            margin-bottom: 30px;
            border: none;
            border-bottom: 1px solid #fff;
            outline: none;
            background: transparent;
        }
        .login-box .user-box label {
            position: absolute;
            top:0;
            left: 0;
            padding: 10px 0;
            font-size: 16px;
            color: #fff;
            pointer-events: none;
            transition: .5s;
        }
        .login-box .user-box input:focus ~ label,
        .login-box .user-box input:valid ~ label {
            top: -20px; 
            left: 0;
            color: #03e9f4;
            font-size: 12px;
        }
        .login-box form a {
            position: relative;
            display: inline-block;
            padding: 10px 20px; 
            color: #03e9f4;
            font-size: 16px;
            text-decoration: none;
            text-transform: uppercase;
            overflow: hidden;
            transition: .5s; 
            margin-top: 40px;
            letter-spacing: 4px;
            border-radius: 5px; /* Tambahkan ini agar background saat hover lebih rapi */
        }
        .login-box a:hover {
            background: #03e9f4;
            color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 5px #03e9f4,
                        0 0 25px #03e9f4,
                        0 0 50px #03e9f4,
                        0 0 100px #03e9f4;
        }
        .login-box a span {
            position: absolute;
            display: block;
            background: #03e9f4;
        }
        .login-box a span:nth-child(1) {
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            animation: btn-anim1 1s linear infinite;
        }
        @keyframes btn-anim1 {
            0% {
                left: -100%;
            }
            50%, 100% {
                left: 100%;
            }
        }
        .login-box a span:nth-child(2) {
            top: -100%;
            right: 0;
            width: 2px;
            height: 100%;
            animation: btn-anim2 1s linear infinite;
            animation-delay: .25s;
        }
        @keyframes btn-anim2 {
            0% {
                top: -100%;
            }
            50%, 100% {
                top: 100%;
            }
        }
        .login-box a span:nth-child(3) {
            bottom: 0;
            right: -100%;
            width: 100%;
            height: 2px;
            animation: btn-anim3 1s linear infinite;
            animation-delay: .5s;
        }
        @keyframes btn-anim3 {
            0% {
                right: -100%;
            }
            50%, 100% {
                right: 100%;
            }
        }
        .login-box a span:nth-child(4) {
            bottom: -100%;
            left: 0;
            width: 2px;
            height: 100%;
            animation: btn-anim4 1s linear infinite;
            animation-delay: .75s;
        }
        @keyframes btn-anim4 {
            0% {
                bottom: -100%;
            }
            50%, 100% {
                bottom: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <form>
            <div class="user-box">
                <input type="email" id="email" aria-describedby="emailHelp" placeholder="Masukkan Email">
            </div>
            <div class="user-box">
                <input type="password" id="password" aria-describedby="passwordHelp" placeholder="Masukkan Password">
            </div>
            <a href="#">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                Login
            </a>
        </form>
    </div>
</body>
</html>
