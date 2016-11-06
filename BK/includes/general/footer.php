<?php
/**
 * Created by PhpStorm.
 * User: Alex.Chernokov
 * Date: 14/10/2016
 * Time: 19:42
 */
?>
</div>

        <div class="footer">
            <div class="pull-right">
10GB of <strong>250GB</strong> Free.
            </div>
            <div>
                <strong>Copyright</strong> Your Company &copy; 2015-2016
</div>
        </div>



    </div>
    <!-- Mainly scripts -->
    <script src="/js/jquery-2.1.1.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <!-- Morris -->
    <script src="/js/plugins/morris/raphael-2.1.0.min.js"></script>
    <script src="/js/plugins/morris/morris.js"></script>
    <!-- Chartist -->
    <script src="/js/plugins/chartist/chartist.min.js"></script>
    <!-- Custom and plugin javascript -->
    <script src="/js/main.js"></script>
    <script src="/js/plugins/pace/pace.min.js"></script>
    <!-- Jvectormap -->
    <script src="/js/plugins/jvectormap/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="/js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
    <!-- Sparkline -->
    <script src="/js/plugins/sparkline/jquery.sparkline.min.js"></script>
    <!-- Sparkline demo data  -->
    <script src="/js/demo/sparkline-demo.js"></script>
    <script src="/js/plugins/chartJs/Chart.min.js"></script>
    <script>
$(document).ready(function () {
    "use strict";
    Morris.Bar({
                element: 'morris-bar-chart',
                data: [{y: '2006', a: 60, b: 50},
                    {y: '2007', a: 75, b: 65},
                    {y: '2008', a: 50, b: 40},
                    {y: '2009', a: 75, b: 65},
                    {y: '2010', a: 50, b: 40},
                    {y: '2011', a: 75, b: 65},
                    {y: '2012', a: 100, b: 90}],
                xkey: 'y',
                ykeys: ['a', 'b'],
                labels: ['Series A', 'Series B'],
                hideHover: 'auto',
                resize: true,
                barColors: ['#1C84C6', '#cacaca'],
            });

            var mapData = {
        "US": 298,
                "SA": 200,
                "DE": 220,
                "FR": 540,
                "CN": 120,
                "AU": 760,
                "BR": 550,
                "IN": 200,
                "GB": 120,
            };

            $('#world-map').vectorMap({
                map: 'world_mill_en',
                backgroundColor: "transparent",
                regionStyle: {
        initial: {
            fill: '#e4e4e4',
                        "fill-opacity": 0.9,
                        stroke: 'none',
                        "stroke-width": 0,
                        "stroke-opacity": 0
                    }
    },
                series: {
        regions: [{
            values: mapData,
                        scale: ["#1ab394", "#1C84C6"],
                        normalizeFunction: 'polynomial'
                    }]
                }
            });
        });
    </script>
</body>
</html>