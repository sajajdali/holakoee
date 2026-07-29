<!DOCTYPE html>
<!--[if lt IE 8]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <title>Webmarket HTML Template - Home Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="ProteusThemes">

    <!--  Google Fonts  -->
    <link href="http://fonts.googleapis.com/css?family=Pacifico|Open+Sans:400,700,400italic,700italic&amp;subset=latin,latin-ext,greek" rel="stylesheet" type="text/css">

    <!-- Twitter Bootstrap -->
    <link href="{{ asset('shop/stylesheets/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('shop/stylesheets/responsive.css') }}" rel="stylesheet">
    <!-- Slider Revolution -->
    <link rel="stylesheet" href="{{ asset('shop/js/rs-plugin/css/settings.css') }}" type="text/css"/>
    <!-- jQuery UI -->
    <link rel="stylesheet" href="{{ asset('shop/js/jquery-ui-1.10.3/css/smoothness/jquery-ui-1.10.3.custom.min.css') }}" type="text/css"/>
    <!-- PrettyPhoto -->
    <link rel="stylesheet" href="{{ asset('shop/js/prettyphoto/css/prettyPhoto.css') }}" type="text/css"/>
    <!-- main styles -->
    <link href="{{ asset('shop/stylesheets/main.css') }}" rel="stylesheet">

    <!-- Modernizr -->
    <script src="{{ asset('shop/js/modernizr.custom.56918.js') }}"></script>

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="{{ asset('shop/images/apple-touch/144.png') }}">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="{{ asset('shop/images/apple-touch/114.png') }}">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="{{ asset('shop/images/apple-touch/72.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('shop/images/apple-touch/57.png') }}">
    <link rel="shortcut icon" href="{{ asset('shop/images/apple-touch/57.png') }}">
    <meta name="robots" content="noindex, nofollow">
</head>

<body class="">
<div class="master-wrapper">

    <!--  ==========  -->
    <!--  = Header =  -->
    <!--  ==========  -->
    <header id="header">
        <div class="container">
            <div class="row">
                <!--  = Logo =  -->
                <div class="span7">
                    <a class="brand" href="index.html">
                        <img src="{{ asset('shop/images/logo.webp') }}" alt="Webmarket Logo" width="187" />
                    </a>
                </div>

                <!--  = Social Icons =  -->
                <div class="span5">
                    <div class="topmost-line">
                        <div class="lang-currency"></div>
                    </div>
                </div> <!-- /social icons -->
            </div>
        </div>
    </header>

    <!--  ==========  -->
    <!--  = Main Menu / navbar =  -->
    <!--  ==========  -->
    <div class="navbar navbar-static-top" id="stickyNavbar">
        <div class="navbar-inner">
            <div class="container">
                <div class="row">
                    <div class="span9">
                        <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>

                        <!--  = Menu =  -->
                        <div class="nav-collapse collapse">
                            <ul class="nav" id="mainNavigation">
                                <li class=" ">
                                    <a href="#" class="dropdown-toggle"> خانه  </a>
                                </li>

                                <li class="">
                                    <a href="#" class="dropdown-toggle"> فروشگاه </a>
                                </li>
                                <li><a href="#">درباره ما</a></li>
                                <li><a href="#">تماس با ما</a></li>
                            </ul>

                            <!--  = Search form =  -->
                            <form class="navbar-form pull-right" action="#" method="get">
                                <button type="submit"><span class="icon-search"></span></button>
                                <input type="text" class="span1" name="search" id="navSearchInput">
                            </form>
                        </div><!-- /.nav-collapse -->
                    </div>

                    <!--  ==========  -->
                    <!--  = Cart =  -->
                    <!--  ==========  -->
                    <div class="span3">
                        <div class="cart-container" id="cartContainer">
                            <div class="cart">
                                <p class="items">سبد خرید <span class="dark-clr">(۰)</span></p>
                                <p class="dark-clر hidden-tablet">۰</p>
                                <a href="checkout-step-1.html" class="btn btn-danger">
                                    <i class="icon-shopping-cart"></i>
                                </a>
                            </div>
                            <div class="open-panel">
                                <div class="summary">
                                    <div class="line">
                                        <div class="row-fluid">
                                            <div class="span6">هزینه ارسال :</div>
                                            <div class="span6 align-right">۰</div>
                                        </div>
                                    </div>
                                    <div class="line">
                                        <div class="row-fluid">
                                            <div class="span6">جمع کل :</div>
                                            <div class="span6 align-right size-16">۰</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="proceed">
                                    <a href="#" class="btn btn-danger pull-right bold higher">تصویه حساب <i class="icon-shopping-cart"></i></a>
                                    <small>هزینه ارسال بر اساس منطقه جغرافیایی محاسبه میشود. <a href="#">اطلاعات بیشتر</a></small>
                                </div>
                            </div>
                        </div>
                    </div> <!-- /cart -->
                </div>
            </div>
        </div>
    </div> <!-- /main menu -->

    <!--  ==========  -->
    <!--  = Slider Revolution =  -->
    <!--  ==========  -->
    <div class="fullwidthbanner-container">
        <div class="fullwidthbanner">
            <ul>
                <li data-transition="premium-random" data-slotamount="3">
                    <img src="{{ asset('shop/images/dummy/slides/4/slide.jpg') }}" alt="slider img" width="1400" height="377" />

                    <!-- faces -->
                    <div class="caption lft ltt"
                         data-x="-150"
                         data-y="0"
                         data-speed="300"
                         data-start="2000"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person1.png') }}" alt="satisfied customer" width="108" height="204" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="0"
                         data-y="0"
                         data-speed="300"
                         data-start="2200"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person2.png') }}" alt="satisfied customer" width="108" height="321" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="500"
                         data-y="0"
                         data-speed="300"
                         data-start="2400"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person3.png') }}" alt="satisfied customer" width="108" height="139" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="720"
                         data-y="0"
                         data-speed="300"
                         data-start="2600"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person4.png') }}" alt="satisfied customer" width="108" height="191" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="940"
                         data-y="0"
                         data-speed="300"
                         data-start="2800"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person5.png') }}" alt="satisfied customer" width="108" height="139" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="1200"
                         data-y="0"
                         data-speed="300"
                         data-start="3000"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person6.png') }}" alt="satisfied customer" width="108" height="179" />
                    </div>
                    <div class="caption lft ltt"
                         data-x="1350"
                         data-y="0"
                         data-speed="300"
                         data-start="3200"
                         data-easing="easeInOutCubic">
                        <img src="{{ asset('shop/images/dummy/slides/4/person7.png') }}" alt="satisfied customer" width="108" height="133" />
                    </div>

                    <!-- texts -->
                    <div class="caption lfl big_theme"
                         data-x="120"
                         data-y="140"
                         data-speed="1000"
                         data-start="500"
                         data-easing="easeInOutBack">
                        بیش از 1000 مشتری خوشنود
                    </div>

                    <div class="caption lfl small_theme"
                         data-x="120"
                         data-y="210"
                         data-speed="1000"
                         data-start="700"
                         data-easing="easeInOutBack">
                        با خیال راحت خرید کنید
                    </div>

                    <a href="http://proteusthemes.ticksy.com/" class="caption lfl btn btn-primary btn_theme"
                       data-x="120"
                       data-y="280"
                       data-speed="1000"
                       data-start="900"
                       data-easing="easeInOutBack">
                        به خوبی شما را پشتیبانی میکنیم
                    </a>
                </li><!-- /slide -->
            </ul>
            <div class="tp-bannertimer"></div>
        </div>
        <!--  ==========  -->
        <!--  = Nav Arrows =  -->
        <!--  ==========  -->
        <div id="sliderRevLeft"><i class="icon-chevron-left"></i></div>
        <div id="sliderRevRight"><i class="icon-chevron-right"></i></div>
    </div> <!-- /slider revolution -->

    <!--  ==========  -->
    <!--  = Main container =  -->
    <!--  ==========  -->
    <div class="container">
        <div class="row">
            <div class="span12">
                <div class="push-up over-slider blocks-spacer">

                    <!--  = Three Banners =  -->
                    <div class="row">
                        <div class="span4">
                            <a href="#" class="btn btn-block banner">
                                <span class="title"><span class="light">فروش</span> تابستانی</span>
                                <em>تا 60% تخفیف روی محصولات ها</em>
                            </a>
                        </div>
                        <div class="span4">
                            <a href="#" class="btn btn-block colored banner">
                                <span class="title"><span class="light">ارسال</span> رایگان</span>
                                <em>برای خرید های بیش از 69000 تومان</em>
                            </a>
                        </div>
                        <div class="span4">
                            <a href="#" class="btn btn-block banner">
                                <span class="title"><span class="light">محصولات</span> جدید</span>
                                <em>از محصولات جدید دیدن کنید.</em>
                            </a>
                        </div>
                    </div> <!-- /three banners -->
                </div>
            </div>
        </div>

        <!--  ==========  -->
        <!--  = Featured Items =  -->
        <!--  ==========  -->
        <div class="row featured-items blocks-spacer">
            <div class="span12">
                <div class="main-titles lined">
                    <h2 class="title"><span class="light">محصولات</span> ویژه</h2>
                    <div class="arrows">
                        <a href="#" class="icon-chevron-right" id="featuredItemsRight"></a>
                        <a href="#" class="icon-chevron-left" id="featuredItemsLeft"></a>
                    </div>
                </div>
            </div>

            <div class="span12">
                <!--  ==========  -->
                <!--  = Carousel =  -->
                <!--  ==========  -->
                <div class="carouFredSel" data-autoplay="false" data-nav="featuredItems">
                    <div class="slide">
                        <div class="row">
                            <!-- Product 1 -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/product.jpg') }}" alt="" width="300" height="300" />
                                            <div class="img-overlay">
                                                <a href="{{url('/checkout/1')}}" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="{{url('/checkout/1')}}" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">۱،۷۵۰،۰۰۰ تومان</h4>
                                        <h5 class="no-margin">رژیم cho</h5>
                                    </div>
                                    <p class="desc">برنامه رژیم تخصصی ویژه کاهش وزن</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/product.jpg') }}" alt="" width="300" height="300" />
                                            <div class="img-overlay">
                                                <a href="{{url('/checkout/2')}}" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="{{url('/checkout/2')}}" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">۳۵۰،۰۰۰ تومان</h4>
                                        <h5 class="no-margin">رژیم 1 ماهه</h5>
                                    </div>
                                    <p class="desc">برنامه رژیم تخصصی ۱ ماهه</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->

                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/product.jpg') }}" alt="" width="300" height="300" />
                                            <div class="img-overlay">
                                                <a href="{{url('/checkout/3')}}" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="{{url('/checkout/3')}}" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">۷۰۰،۰۰۰ تومان</h4>
                                        <h5 class="no-margin">رژیم ۳ ماه</h5>
                                    </div>
                                    <p class="desc">برنامه رژیم تخصصی ویژه ۳ ماهه کاهش وزن</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/product.jpg') }}" alt="" width="300" height="300" />
                                            <div class="img-overlay">
                                                <a href="{{url('/checkout/4')}}" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="{{url('/checkout/4')}}" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">۱،۶۵۰،۰۰۰ تومان</h4>
                                        <h5 class="no-margin">رژیم ۶ ماهه</h5>
                                    </div>
                                    <p class="desc">برنامه رژیم ۶ ماهه کاهش وزن</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                        </div>
                    </div>
                    <div class="slide">
                        <div class="row">
                            <!-- Product 4 -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/dummy/featured-products/featured-1.png') }}" alt="" width="518" height="358" />
                                            <div class="img-overlay">
                                                <a href="#" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="#" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">$41</h4>
                                        <h5 class="no-margin">محصول ویژه 515</h5>
                                    </div>
                                    <p class="desc">توضیحاتی که در مورد محصول لازم است را در اینجا مینویسید</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                            <!-- Product 5 -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/dummy/featured-products/featured-2.png') }}" alt="" width="518" height="358" />
                                            <div class="img-overlay">
                                                <a href="#" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="#" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">$107</h4>
                                        <h5 class="no-margin">محصول ویژه 690</h5>
                                    </div>
                                    <p class="desc">توضیحاتی که در مورد محصول لازم است را در اینجا مینویسید</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star"></span>
                                        <span class="icon-star"></span>
                                        <span class="icon-star"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                            <!-- Product 6 -->
                            <div class="span4">
                                <div class="product">
                                    <div class="product-img featured">
                                        <div class="picture">
                                            <img src="{{ asset('shop/images/dummy/featured-products/featured-3.png') }}" alt="" width="518" height="358" />
                                            <div class="img-overlay">
                                                <a href="#" class="btn more btn-primary">توضیحات بیشتر</a>
                                                <a href="#" class="btn buy btn-danger">خرید</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="main-titles">
                                        <h4 class="title">$61</h4>
                                        <h5 class="no-margin">محصول ویژه 405</h5>
                                    </div>
                                    <p class="desc">توضیحاتی که در مورد محصول لازم است را در اینجا مینویسید</p>
                                    <p class="center-align stars">
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star stars-clr"></span>
                                        <span class="icon-star"></span>
                                        <span class="icon-star"></span>
                                    </p>
                                </div>
                            </div> <!-- /product -->
                        </div>
                    </div>
                </div> <!-- /carousel -->
            </div>
        </div>
    </div> <!-- /container -->

    <!--  ==========  -->
    <!--  = Footer =  -->
    <!--  ==========  -->
    <footer>
        <div class="foot-dark">
            <div class="container">

                <div class="row">
                    <!--  = Menu 1 =  -->
                    <div class="span3">
                        <div class="main-titles lined">
                            <h3 class="title"><span class="light"></span> اصلی</h3>
                        </div>
                        <ul class="nav bold">
                            <li><a href="#">خانه</a></li>
                            <li><a href="#">صفحات</a></li>
                            <li><a href="#">درباره ما</a></li>
                            <li><a href="#">کد های میانبر</a></li>
                            <li><a href="#">گالری</a></li>
                            <li><a href="#">تماس با ما</a></li>
                        </ul>
                    </div>
                    <!-- سایر ستون‌ها در صورت نیاز -->

                    <div class="span3">
                        <div class="main-titles lined">
                            <h3 class="title"><span class="light"></span> تماس با ما</h3>
                        </div>
                        <div style="direction: rtl; text-align: right; color: #cccccc; line-height: 2; font-size: 13px;">
                            <p style="margin: 0 0 8px;">
                                <strong style="color: #ffffff;">تلفن:</strong>
                                <br>
                                <a href="tel:01732537093" style="color: #cccccc; text-decoration: none;">01732537093</a>
                            </p>
                            <p style="margin: 0;">
                                <strong style="color: #ffffff;">آدرس:</strong>
                                استان گلستان، شهرستان گرگان، بخش مرکزی، شهر گرگان، محله شالیکوبی شرقی، کوچه شاهینی 7 [شهید بدخش]، خیابان عدالت 16، کوثر، پلاک 0.0، طبقه 3، واحد شرقی
                            </p>
                        </div>
                    </div>

                    <div class="span3">
                        <div class="main-titles lined">
                            <h3 class="title"><span class="light">enamad</span> </h3>
                        </div>
                        <a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=651783&Code=B5yEo2CIw8cMvHoa9s32SeeQmP5OPUg3'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=651783&Code=B5yEo2CIw8cMvHoa9s32SeeQmP5OPUg3' alt='' style='cursor:pointer' code='B5yEo2CIw8cMvHoa9s32SeeQmP5OPUg3'></a>
                    </div>
                </div>
            </div>
        </div> <!-- /middle footer -->
    </footer> <!-- /footer -->

    <!--  ==========  -->
    <!--  = Modal Windows =  -->
    <!--  ==========  -->
    <!-- Login / Register ... -->

</div> <!-- end of master-wrapper -->

<!--  ==========  -->
<!--  = JavaScript =  -->
<!--  ==========  -->

<!--  = FB =  -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=126780447403102";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>

<!--  = jQuery - CDN with local fallback =  -->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
    if (typeof jQuery == 'undefined') {
        document.write('<script src="{{ asset('shop/js/jquery.min.js') }}"><\/script>');
    }
</script>

<!--  = _ =  -->
<script src="{{ asset('shop/js/underscore/underscore-min.js') }}" type="text/javascript"></script>

<!--  = Bootstrap =  -->
<script src="{{ asset('shop/js/bootstrap.min.js') }}" type="text/javascript"></script>

<!--  = Slider Revolution =  -->
<script src="{{ asset('shop/js/rs-plugin/pluginsources/jquery.themepunch.plugins.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('shop/js/rs-plugin/js/jquery.themepunch.revolution.min.js') }}" type="text/javascript"></script>

<!--  = CarouFredSel =  -->
<script src="{{ asset('shop/js/jquery.carouFredSel-6.2.1-packed.js') }}" type="text/javascript"></script>

<!--  = jQuery UI =  -->
<script src="{{ asset('shop/js/jquery-ui-1.10.3/js/jquery-ui-1.10.3.custom.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('shop/js/jquery-ui-1.10.3/touch-fix.min.js') }}" type="text/javascript"></script>

<!--  = Isotope =  -->
<script src="{{ asset('shop/js/isotope/jquery.isotope.min.js') }}" type="text/javascript"></script>

<!--  = Tour =  -->
<script src="{{ asset('shop/js/bootstrap-tour/build/js/bootstrap-tour.min.js') }}" type="text/javascript"></script>

<!--  = PrettyPhoto =  -->
<script src="{{ asset('shop/js/prettyphoto/js/jquery.prettyPhoto.js') }}" type="text/javascript"></script>

<!--  = Google Maps API =  -->
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="{{ asset('shop/js/goMap/js/jquery.gomap-1.3.2.min.js') }}"></script>

<!--  = Custom JS =  -->
<script src="{{ asset('shop/js/custom.js') }}" type="text/javascript"></script>

</body>
</html>
