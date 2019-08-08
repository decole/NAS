function re(data) {
    if (data == 0) return "off";
    if (data == 1) return "on";
}

$(document).ready(function () {
    $(".relay-control[data-id]").click(function () {
        let $this = $(this);
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

    $(".emergencyStop[data-id]").click(function () {
        let action
        let $this = $(this);
        action = 'on';
        if($(".stateEmergencyStop").hasClass("bg-yellow") || $(".stateEmergencyStop").hasClass("bg-green")) {
            action = 'on';
        }
        if($(".stateEmergencyStop").hasClass("bg-red")) {
            action = 'off';
        }
        $.get("/api/emergency-stop?action="+action+"&topic="+$this.data("id"), function (data) {
            if (data == 0) {
                $(".stateEmergencyStop").removeClass("bg-yellow").removeClass("bg-red").addClass("bg-green");
            }
            if (data == 1) {
                $(".stateEmergencyStop").removeClass("bg-yellow").removeClass("bg-green").addClass("bg-red");
            }
        });
    });

    function leakage() {
        if($("div").is($(".leakage-control[data-id]"))) {
            let $this = $(".leakage-control[data-id]");
            $.get("/api/leakage?topic="+$this.data("id"), function (data) {
                if (data == 0) {
                    $(".leakage-status[data-id='"+$this.data("id")+"']").text("Норма");
                    $this.removeClass('bg-yellow').removeClass('bg-red').addClass('bg-green');
                }
                if (data == 1) {
                    $(".leakage-status[data-id='"+$this.data("id")+"']").text("Протечка");
                    $this.removeClass('bg-yellow').removeClass('bg-green').addClass('bg-red');
                    console.log("alarma!");
                    $('#carteSoudCtrl')[0].play();
                }
            });
            setTimeout(leakage, 10000);
        }
    }

    leakage();

    function stateRelays() {
        $(".relay-control[data-id]").map(function (key, value) {
            topic = $(value).data("id");
            $.get("/api/relay-state?topic="+topic, function (data) {
                if (data == 1) {
                    $(value).parent().removeClass('bg-yellow').removeClass('bg-red').addClass('bg-green');
                }
                if (data == 0) {
                    console.log();
                    $(value).parent().removeClass('bg-yellow').removeClass('bg-green').addClass('bg-red');
                }
            });
        });
        setTimeout(stateRelays, 5000);
    }

    stateRelays();

// use chart.js to make cool graphics
    var charter = $('.sensorchart[data-topic]');
    var chartTopic = []; // topics array with objects chart.js and lineCharts for chartjs
    var chartOptions = {
        legend:false,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero:false
                }
            }],
            xAxes: [{
                display: false
            }]
        },
    };

    function renderDate(charter, date, chartTopic){
        charter.map(function (key, value) {
            if(!date){
                date = 'current';
            }

            var nameTopic = $(value).data('topic');
            var topic = value['id'];
            var ctx = document.getElementById(topic);

            if (date === 'current') {
                $.get('/api/chart', {'date': 'current', 'topic': nameTopic}).done(function (dataChart) {
                    chartTopic[nameTopic] = new Chart(ctx, {
                        type: 'line',
                        data:  dataChart,
                        options: chartOptions,
                    });
                });
            }
            else {
                $.get('/api/chart', {'date': date, 'topic': nameTopic}).done(function (dataChart) {
                    // addData(chartTopic[nameTopic],dataChart['labels'],dataChart['datasets'])
                    // chartTopic[nameTopic].clear();
                    chartTopic[nameTopic].config.data = dataChart;
                    chartTopic[nameTopic].update();
                });
            }

        });
    }

    renderDate(charter, 'current', chartTopic);

    function isCurrentDateForm() {
        if ($('#isDate').val() == 'current') {
            $('#isDate').val(0);
        }
    }

    function prevDate() {
        //http://momentjs.com/docs/#/parsing/date/
        isCurrentDateForm();
        var date = $('#isDate').val();
        date--;
        $('#isDate').val(date);
        date = date * (-1);
        date = moment().subtract(date, 'days').format('YYYY-MM-DD');
        console.log(date);
        $(".dateIs").html(date);
        renderDate(charter, date, chartTopic);
    }

    function nextDate() {
        //http://momentjs.com/docs/#/parsing/date/
        isCurrentDateForm();
        var date = $('#isDate').val();
        date++;
        $('#isDate').val(date);
        date = moment().add(date, 'days').format('YYYY-MM-DD');
        console.log(date);
        $('.dateIs').html(date);
        renderDate(charter, date, chartTopic);
    }

    $('#prev-date').click(function(){ prevDate(); });
    $('#next-date').click(function(){ nextDate(); });

    function stateSensors() {
        $(".sensor-state[data-topic]").map(function (key, value) {
            topic = $(value).data("topic");
            $.get("/api/sensor-state?topic="+topic, function (data) {
                $(value).html(data);
            });
        });
        setTimeout(stateSensors, 10000);
    }

    stateSensors();
});
