<div class="row mb-4">
    <div class="col-12">
        <table class="table table-hover table-bordered bg-light mb-0">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">Service Plan</th>
                    <th scope="col">Total invoiced</th>
                    <th scope="col">Total paid</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['servicePlans'] as $servicePlan) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($servicePlan['name'], ENT_QUOTES); ?></td>
                        <td><?php echo number_format($servicePlan['totalIssued'], 2) . ' ' . $result['currency']; ?></td>
                        <td><?php echo number_format($servicePlan['totalPaid'], 2) . ' ' . $result['currency']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <div class="embed-responsive embed-responsive-4by3">
            <div id="chart-total-invoiced" class="embed-responsive-item"></div>
        </div>
    </div>
    <div class="col-6">
        <div class="embed-responsive embed-responsive-4by3">
            <div id="chart-total-paid" class="embed-responsive-item"></div>
        </div>
    </div>
</div>

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
                foreach ($result['servicePlans'] as $servicePlan) {
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
                foreach ($result['servicePlans'] as $servicePlan) {
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
