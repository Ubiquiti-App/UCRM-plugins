<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revenue report</title>
</head>
<body>
<h1>Revenue report</h1>
<form>
    <table>
        <tr>
            <th>Service Plan</th>
            <th>Total invoiced</th>
            <th>Total paid</th>
        </tr>
        <?php foreach ($servicePlans as $servicePlan) { ?>
            <tr>
                <td><?php echo htmlspecialchars($servicePlan['name'], ENT_QUOTES); ?></td>
                <td><?php echo number_format($servicePlan['totalIssued'], 2) . ' ' . $currency; ?></td>
                <td><?php echo number_format($servicePlan['totalPaid'], 2) . ' ' . $currency; ?></td>
            </tr>
        <?php } ?>
    </table>
</form>
<div id="chart-total-invoiced" style="width: 500px; height: 350px;"></div>
<div id="chart-total-paid" style="width: 500px; height: 350px;"></div>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        drawChart(
            'chart-total-invoiced',
            'Total invoiced per service plan',
            [
                ['Service Plan', 'Total invoiced'],
                <?php
                foreach ($servicePlans as $servicePlan) {
                    printf(
                        '[%s, %F],',
                        json_encode($servicePlan['name']),
                        $servicePlan['totalIssued']
                    );
                }
                ?>
            ]
        );

        drawChart(
            'chart-total-paid',
            'Total paid per service plan',
            [
                ['Service Plan', 'Total paid'],
                <?php
                foreach ($servicePlans as $servicePlan) {
                    printf(
                        '[%s, %F],',
                        json_encode($servicePlan['name']),
                        $servicePlan['totalPaid']
                    );
                }
                ?>
            ]
        );
    }

    function drawChart(id, title, data) {
        var dataTable = google.visualization.arrayToDataTable(data);

        var options = {
            title: title
        };

        var chart = new google.visualization.PieChart(document.getElementById(id));

        chart.draw(dataTable, options);
    }
</script>
</body>
</html>
