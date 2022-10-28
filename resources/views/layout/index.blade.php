<!DOCTYPE html>
<html lang="en">
<head>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8235722100578452"
     crossorigin="anonymous"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="icon" href="https://yt3.ggpht.com/aIfc-Ne3lkKzxUT3tePy_gJrbhbTPLPQdHfMDDr96RVBRyp9GN9pjg9V7cqIjkj8OXh6wB5sRg=s88-c-k-c0x00ffffff-no-rj" type="image/x-icon">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <title>Tool Gecko</title>
</head>
<body>
    <div class="container pt-3 pb-3">
      <div class="header border-bottom row align-items-center d-flex justify-content-center pt-4 pb-4">
        <div class="col-4 d-flex align-items-center">
          <img src="https://yt3.ggpht.com/aIfc-Ne3lkKzxUT3tePy_gJrbhbTPLPQdHfMDDr96RVBRyp9GN9pjg9V7cqIjkj8OXh6wB5sRg=s88-c-k-c0x00ffffff-no-rj" alt="">
        </div>
        <div class="col-4 text-center">
          <h1 style="font-family: cursive;">Tool Gecko</h1>
        </div>
        <div class="col-4 text-right">
          <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              @if (session("authenticated"))
                Xin chào {{ session("authenticated") }} !
              @else
                Tài khoản
              @endif
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
              @if (session("authenticated"))
                <a class="dropdown-item" href="{{ route("logout") }}">Đăng xuất</a>
              @else
                <a class="dropdown-item" href="{{ route("login") }}">Đăng nhập</a>
              @endif
            </div>
          </div>
        </div>
      </div>
        @yield('content')
      <div class="box-contact" style="position: fixed;right: 50px;bottom: 10px;">
        <div>
          <p>Contact: </p>
          <address>
            <a href="mailto:vodinh2000ht@gmail.com">📧 vodinh2000ht@gmail.com</a><br>
            <a href="tel:0329766459">☎ 0329766459</a>
          </address>
        </div>
      </div>
    </div>
  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  @yield('script')
</body>
</html>
