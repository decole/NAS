function re(data) {
    if (data == 0) return "off";
    if (data == 1) return "on";
}

$(document).ready(function () {
    $(".relay-control[data-id]").click(function () {
        var $this = $(this);
        $.get("/api/relay?a="+$this.data("id")+"&r="+Number(!$this.hasClass("on")), function (data) {
            $(".relay-status[data-id='"+$this.data("id")+"']").text(re(data));
            if (data == 0) {
                $this.removeClass("on").addClass("off");
            }
            if (data == 1) {
                $this.removeClass("off").addClass("on");
            }
        });

    });

    /* ChartJS
    * -------
    * Here we will create a few charts using ChartJS
    */

var areaChartOptions = {
    //Boolean - If we should show the scale at all
    showScale: true,
    //Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines: false,
    //String - Colour of the grid lines
    scaleGridLineColor: "rgba(0,0,0,.05)",
    //Number - Width of the grid lines
    scaleGridLineWidth: 1,
    //Boolean - Whether to show horizontal lines (except X axis)
    scaleShowHorizontalLines: true,
    //Boolean - Whether to show vertical lines (except Y axis)
    scaleShowVerticalLines: true,
    //Boolean - Whether the line is curved between points
    bezierCurve: true,
    //Number - Tension of the bezier curve between points
    bezierCurveTension: 0.3,
    //Boolean - Whether to show a dot for each point
    pointDot: false,
    //Number - Radius of each point dot in pixels
    pointDotRadius: 4,
    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth: 1,
    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius: 20,
    //Boolean - Whether to show a stroke for datasets
    datasetStroke: true,
    //Number - Pixel width of dataset stroke
    datasetStrokeWidth: 2,
    //Boolean - Whether to fill the dataset with a color
    datasetFill: true,
    //String - A legend template
    legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].lineColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
    //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
    maintainAspectRatio: false,
    //Boolean - whether to make the chart responsive to window resizing
    responsive: true
};

    //-------------
    //- LINE CHART -
    //--------------
    if (typeof areaChartData1 !== 'undefined') {
        var lineChartCanvas1 = $("#lineChart1").get(0).getContext("2d");
        var lineChart1 = new Chart(lineChartCanvas1);
        var lineChartOptions1 = areaChartOptions;
        lineChartOptions1.datasetFill = false;
        lineChart1.Line(areaChartData1, lineChartOptions1);
    }
    if (typeof areaChartData2 !== 'undefined') {
        var lineChartCanvas2 = $("#lineChart2").get(0).getContext("2d");
        var lineChart2 = new Chart(lineChartCanvas2);
        var lineChartOptions2 = areaChartOptions;
        lineChartOptions2.datasetFill = false;
        lineChart2.Line(areaChartData2, lineChartOptions2);
    }
    if (typeof areaChartData3 !== 'undefined') {
        var lineChartCanvas3 = $("#lineChart3").get(0).getContext("2d");
        var lineChart3 = new Chart(lineChartCanvas3);
        var lineChartOptions3 = areaChartOptions;
        lineChartOptions3.datasetFill = false;
        lineChart3.Line(areaChartData3, lineChartOptions3);
    }
    if (typeof areaChartData4 !== 'undefined') {
        var lineChartCanvas4 = $("#lineChart4").get(0).getContext("2d");
        var lineChart4 = new Chart(lineChartCanvas4);
        var lineChartOptions4 = areaChartOptions;
        lineChartOptions4.datasetFill = false;
        lineChart4.Line(areaChartData4, lineChartOptions4);
    }
});