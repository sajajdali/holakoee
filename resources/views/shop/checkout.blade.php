<!DOCTYPE html>
<!--[if lt IE 8]>      <html class="no-js lt-ie10 lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <title>Webmarket HTML Template - Checkout Step 4</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="ProteusThemes">

    <!--  Google Fonts  -->
    <link href="http://fonts.googleapis.com/css?family=Pacifico|Open+Sans:400,700,400italic,700italic&amp;subset=latin,latin-ext,greek" rel="stylesheet" type="text/css">

    <!-- CSS -->
    <link href="{{ asset('shop/stylesheets/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('shop/stylesheets/responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('shop/js/rs-plugin/css/settings.css') }}" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('shop/js/jquery-ui-1.10.3/css/smoothness/jquery-ui-1.10.3.custom.min.css') }}" type="text/css"/>
    <link rel="stylesheet" href="{{ asset('shop/js/prettyphoto/css/prettyPhoto.css') }}" type="text/css"/>
    <link href="{{ asset('shop/stylesheets/main.css') }}" rel="stylesheet">

    <!-- Modernizr -->
    <script src="{{ asset('shop/js/modernizr.custom.56918.js') }}"></script>

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="{{ asset('shop/images/apple-touch/144.png') }}">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="{{ asset('shop/images/apple-touch/114.png') }}">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="{{ asset('shop/images/apple-touch/72.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('shop/images/apple-touch/57.png') }}">
    <link rel="shortcut icon" href="{{ asset('shop/images/apple-touch/57.png') }}">
</head>

<body class="checkout-page">

<div class="master-wrapper">

    <div class="container">
        <div class="row">

            <!--  = Main content =  -->
            <section class="span12">
                <div class="checkout-container">
                    <div class="row">
                        <div class="span10 offset1">

                            <!-- Header -->
                            <header>
                                <div class="row">
                                    <div class="span2">
                                        <a href="#">
                                            <img src="{{ asset('shop/images/logo.webp') }}" alt="Webmarket Logo"  height="140" />
                                        </a>
                                    </div>
                                    <div class="span6">
                                        <div class="center-align">
                                            <h1><span class="light">تاييد</span> و پرداخت</h1>
                                        </div>
                                    </div>
                                    <div class="span2">
                                        <div class="right-align">
                                            <img src="{{ asset('shop/images/buttons/security.jpg') }}" alt="Security Sign" width="92" height="65" />
                                        </div>
                                    </div>
                                </div>
                            </header>

                            <!-- Steps -->
                            <div class="checkout-steps">
                                <div class="clearfix">
                                    <div class="step done">
                                        <div class="step-badge"><i class="icon-ok"></i></div>
                                        <a href="checkout-step-1.html">سبد خريد</a>
                                    </div>
                                    <div class="step done">
                                        <div class="step-badge"><i class="icon-ok"></i></div>
                                        <a href="checkout-step-2.html">آدرس ارسال</a>
                                    </div>
                                    <div class="step done">
                                        <div class="step-badge"><i class="icon-ok"></i></div>
                                        <a href="checkout-step-3.html">شيوه پرداخت</a>
                                    </div>
                                    <div class="step active">
                                        <div class="step-badge">4</div>
                                        تاييد و پرداخت
                                    </div>
                                </div>
                            </div>

                            <!-- Items -->
                            @php
                                switch ($id) {
                                    case 1:
                                        $productName = "رژیم cho";
                                        $price = "۱،۷۵۰،۰۰۰ تومان";
                                        break;
                                    case 2:
                                        $productName = "رژیم ۱ ماهه";
                                        $price = "۳۵۰،۰۰۰ تومان";
                                        break;
                                    case 3:
                                        $productName = "رژیم ۳ ماهه";
                                        $price = "۷۰۰،۰۰۰ تومان";
                                        break;
                                    case 4:
                                        $productName = "رژیم ۶ ماهه";
                                        $price = "۱،۶۵۰،۰۰۰ تومان";
                                        break;
                                    default:
                                        $productName = "نامشخص";
                                        $price = "۰ تومان";
                                }
                            @endphp


                            <table class="table table-items">
                                <thead>
                                <tr>
                                    <th colspan="2">آيتم</th>
                                    <th><div class="align-center">تعداد</div></th>
                                    <th><div class="align-right">قيمت</div></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="image">
                                        <img src="{{ asset('shop/images/product.jpg') }}" alt="" width="124" height="124" />
                                    </td>
                                    <td class="desc">{{ $productName }}</td>
                                    <td class="qty">1</td>
                                    <td class="price">{{ $price }}</td>
                                </tr>

                                <tr>
                                    <td colspan="2" rowspan="2">&nbsp;</td>
                                    <td class="stronger">هزينه ارسال :</td>
                                    <td class="stronger"><div class="align-right">رایگان</div></td>
                                </tr>
                                <tr>
                                    <td class="stronger">جمع کل :</td>
                                    <td class="stronger"><div class="size-16 align-right">{{ $price }}</div></td>
                                </tr>
                                </tbody>
                            </table>

                            <p class="right-align">
                                <a href="javascript:void(0)"
                                   class="btn btn-primary higher bold"
                                   onclick="alert('هم اکنون درگاه پرداخت غیر فعال است و به زودی فعال می‌گردد');">
                                    تاييد و پرداخت
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>

<!-- JavaScript -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=126780447403102";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script>if (typeof jQuery == 'undefined') {document.write('<script src="{{ asset('shop/js/jquery.min.js') }}"><\/script>');}</script>

<script src="{{ asset('shop/js/underscore/underscore-min.js') }}"></script>
<script src="{{ asset('shop/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('shop/js/rs-plugin/pluginsources/jquery.themepunch.plugins.min.js') }}"></script>
<script src="{{ asset('shop/js/rs-plugin/js/jquery.themepunch.revolution.min.js') }}"></script>
<script src="{{ asset('shop/js/jquery.carouFredSel-6.2.1-packed.js') }}"></script>
<script src="{{ asset('shop/js/jquery-ui-1.10.3/js/jquery-ui-1.10.3.custom.min.js') }}"></script>
<script src="{{ asset('shop/js/jquery-ui-1.10.3/touch-fix.min.js') }}"></script>
<script src="{{ asset('shop/js/isotope/jquery.isotope.min.js') }}"></script>
<script src="{{ asset('shop/js/bootstrap-tour/build/js/bootstrap-tour.min.js') }}"></script>
<script src="{{ asset('shop/js/prettyphoto/js/jquery.prettyPhoto.js') }}"></script>
<script src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script src="{{ asset('shop/js/goMap/js/jquery.gomap-1.3.2.min.js') }}"></script>
<script src="{{ asset('shop/js/custom.js') }}"></script>

</body>
</html>
