/* ==========================================================================
   File: assets/js/dashboard-custom.js
   Description: Custom ApexCharts configuration with Green/Blue Theme
   ========================================================================== */

$(window).on("load", function () {
  "use strict";

  // --- THEME COLORS ---
  var $primary = "#00D894"; // Green (Main)
  var $secondary = "#0071BC"; // Blue (Secondary)
  var $danger = "#ea5455"; // Red
  var $textMuted = "#b9b9c3";
  var $strokeColor = "#ebe9f1";

  // --- 1. Support Tracker Chart (Circular Progress) ---
  var $supportTrackerChart = document.querySelector("#support-trackers-chart");

  if ($supportTrackerChart) {
    var completionRate =
      $supportTrackerChart.getAttribute("data-percentage") || 0;

    var supportTrackerChartOptions = {
      chart: {
        height: 270,
        type: "radialBar",
      },
      plotOptions: {
        radialBar: {
          size: 150,
          offsetY: 20,
          startAngle: -150,
          endAngle: 150,
          hollow: {
            size: "65%",
          },
          track: {
            background: "#fff",
            strokeWidth: "100%",
          },
          dataLabels: {
            name: {
              offsetY: -5,
              color: $textMuted,
              fontSize: "1rem",
            },
            value: {
              offsetY: 15,
              color: $textMuted,
              fontSize: "1.714rem",
            },
          },
        },
      },
      // Gradient from Green to Blue
      colors: [$primary],
      fill: {
        type: "gradient",
        gradient: {
          shade: "dark",
          type: "horizontal",
          shadeIntensity: 0.5,
          gradientToColors: [$secondary], // Fade to Blue
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 1,
          stops: [0, 100],
        },
      },
      stroke: {
        dashArray: 8,
      },
      series: [completionRate],
      labels: ["Taux de réussite"],
    };

    var supportTrackerChartRender = new ApexCharts(
      $supportTrackerChart,
      supportTrackerChartOptions
    );
    supportTrackerChartRender.render();
  }

  // --- 2. Orders Chart (Area Chart for Daily Appointments) ---
  var $orderChart = document.querySelector("#order-chart");
  var $chartDataInput = document.getElementById("chart-data-series");

  if ($orderChart && $chartDataInput) {
    var chartData = JSON.parse($chartDataInput.value || "[]");

    var orderChartOptions = {
      chart: {
        height: 100,
        type: "area",
        toolbar: {
          show: false,
        },
        sparkline: {
          enabled: true,
        },
        grid: {
          show: false,
          padding: {
            left: 0,
            right: 0,
          },
        },
      },
      // Use Secondary Blue for this chart
      colors: [$secondary],
      stroke: {
        width: 2,
        curve: "smooth",
      },
      fill: {
        type: "gradient",
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.7,
          opacityTo: 0.2,
          stops: [0, 100],
        },
      },
      series: [
        {
          name: "Rendez-vous",
          data: chartData,
        },
      ],
      xaxis: {
        labels: {
          show: false,
        },
        axisBorder: {
          show: false,
        },
      },
      yaxis: [
        {
          y: 0,
          offsetX: 0,
          padding: {
            left: 0,
            right: 0,
          },
        },
      ],
      tooltip: {
        x: { show: false },
      },
    };

    var orderChartRender = new ApexCharts($orderChart, orderChartOptions);
    orderChartRender.render();
  }
});
