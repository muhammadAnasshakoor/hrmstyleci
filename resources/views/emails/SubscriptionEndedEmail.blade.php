<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Ended Notification</title>
</head>
<body>
    <h2>Subscription Ended Notification</h2>

    <p>Hello {{ $data['name'] }},</p>

    <p>We regret to inform you that your subscription has been ended as of {{ $data['subscription_ending_date'] }}. As a result, your account will be suspended on the suspension date, which is {{ $data['acount_suspention_date'] }}.</p>

    <p>If you wish to continue using our services, please consider renewing your subscription as soon as possible.</p>

    <p>If you have any questions or need further assistance, please don't hesitate to contact our support team at waqas.logicalcreations@gmail.com .</p>

    <p>Thank you for being a valued user of our service.</p>
</body>
</html>
