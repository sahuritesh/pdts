<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <style type="text/css">
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            Margin: 0 auto !important;
        }

        table table table {
            table-layout: auto;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* What it does: A work-around for iOS meddling in triggered links. */
        .mobile-link--footer a,
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: underline !important;
        }
    </style>
    <!-- Progressive Enhancements -->
    <style>
        /* What it does: Hover styles for buttons */
        .button-td,
        .button-a {
            transition: all 100ms ease-in;
        }

        .button-td:hover,
        .button-a:hover {
            background: #555555 !important;
            border-color: #555555 !important;
        }

        /* Media Queries */
        @media screen and (max-width: 600px) {
            .email-container {

                width: 100% !important;

                margin: auto !important;

            }

            /* What it does: Forces elements to resize to the full width of their container. Useful for resizing images beyond their max-width. */
            .fluid,
            .fluid-centered {
                max-width: 100% !important;
                height: auto !important;
                Margin-left: auto !important;
                Margin-right: auto !important;
            }

            /* And center justify these ones. */
            .fluid-centered {
                Margin-left: auto !important;
                Margin-right: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }

            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                Margin-left: auto !important;
                Margin-right: auto !important;
                float: none !important;
            }

            table.center-on-narrow {
                display: inline-block !important;
            }

            .skyblue-color {
                color: #3CC8B5;
            }
        }
    </style>
</head>
<body>
    <center>
        <!-- Email Header : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;/* box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19); */border-top: 1px solid #31423d;border-left: 1px solid #31423d;border-right: 1px solid #31423d;" class="email-container">
            <tr style="background-color: #08265D;">
                <td style="text-align: center;width: 150px;padding: 12px;">
                    <!--<h3 style="color:#FFE610;">{{  env('APP_NAME') }} </h3>-->
					<!-- <h3 style="color:#FFE610;">The Endometriosis Foundation of India</h3>-->
					<img src="{{ getAssetUrl('images/logo-pdts.svg') }}" alt="{{ config('app.name') }}" height="90">
                </td>
            </tr>
        </table>
        <table cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#ffffff" width="600" style="margin: auto;/* box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19); *//* border-top: 3px solid #31423d; */border-left: 1px solid #31423d;border-right: 1px solid #31423d;" class="email-container">
            <tr>
                <td style="font-family: Arial, Helvetica, sans-serif; mso-height-rule: exactly; line-height: 15px; color: #31423d;font-size: 13px;">
                    {!! $email_body !!}
                </td>
            </tr>
            <tr style="background-color:  #08265D;">
                <td style="padding: 10px 10px;width: 100%;font-size: 12px; font-family: sans-serif; mso-height-rule: exactly; line-height:18px; text-align: center; color: #ffffff;">
                    <webversion style="color:#ffffff;">© <?= date('Y'); ?> {{ config('app.name') }}</webversion>
                </td>
            </tr>
            <!-- 1 Column Text : BEGIN -->
            <!-- Email Footer : BEGIN -->
            <table cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
            </table>

            <!-- Email Footer : END -->
        </table>
        <!-- Email Body : END -->
    </center>
</body>
</html>