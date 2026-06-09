
/*var chartnew;
function visitsChart(){}
var options = {
  series: [{
      name: "count",
      type: "column",
      data: [23, 42, 35, 27]
  }],
  chart: {
      height: 280,
      type: "line",
      toolbar: {
          show: !1
      }
  },
  stroke: {
      width: [0, 3],
      curve: "smooth"
  },
  plotOptions: {
      bar: {
          horizontal: !1,
          columnWidth: "20%"
      }
  },
  dataLabels: {
      enabled: !1
  },
  legend: {
      show: !1
  },
  colors: ["#2CAFFE", "#1cbb8c"],
  labels: ["Calls", "Initial Visits", "Follow-ups", "Closed"]
},
chartnew = new ApexCharts(document.querySelector("#line-column-chart12"), options);
chartnew.render();

var chart2; // Ensure the chart2 variable is defined globally

// Function to load chart data
function loadChart2Data(select_date='') {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var dataToSend = {
        select_date: select_date // Add other data as needed
    };
    fetch(baseURL + "postLeadsStatusCnt", {
        method: 'POST', // Use POST method
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, // Include CSRF token in headers            
        },
        body: JSON.stringify(dataToSend) // Send the data as a JSON string
    })
    .then(response => response.json())
    .then(data => {
        if (chart2) {
            // Update the series if the chart already exists
            chart2.updateOptions({
                series: [{
                    data: data.series
                }]
            });
        } else {
            // Create the chart if it doesn't exist
            //console.log(data.series);
            console.log(data.labels[0]);
            var options = {
                series: [{
                    name: 'Count', // Optionally provide a name for the series
                    data: data.series
                }],
                chart: {
                    height: 380,
                    type: 'bar',
                    events: {
                        click: function(chart, w, e) {
                            // Handle click event if needed
                        }
                    }
                },
                colors: ["#2CAFFE", "#1cbb8c", "#feb019"],
                plotOptions: {
                    bar: {
                        columnWidth: '20%',
                        distributed: true,
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: false
                },
                xaxis: {
                    categories: data.labels[0],
                    labels: {
                        style: {
                            colors: ["#2CAFFE", "#1cbb8c", "#feb019"],
                            fontSize: '12px'
                        }
                    }
                },
                toolbar: {
                    show: false // Set this to false to hide the toolbar
                }
            };
            chart2 = new ApexCharts(document.querySelector("#chart"), options);
            chart2.render();
        }
    })
    .catch(error => console.log('Error loading the data: ' + error));
}

/*var chart;
// Function to load chart data
function loadChartData() {
  var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  fetch(baseURL + "postServiceCompletedCnt", {
    method: 'POST', // Use POST method
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken // Include CSRF token in headers
    }
  })
    .then(response => response.json())
    .then(data => {
        if (chart) {
            // Update the series and labels if the chart already exists
            chart.updateOptions({
                series: data.series,
                labels: ''
            });
        } else {
            // Create the chart if it doesn't exist
           // var series_val = JSON.stringify(data.series);
            //console.log(series_val);
           console.log(data.series);
            var options = {
              series: data.series,
              labels: data.labels,
              chart: {
                type: 'donut',
                width: 380,
                height: 410                
              },            
            legend: {
              position: 'bottom'
            },
            };
            chart = new ApexCharts(document.querySelector("#pie"), options);
            chart.render();
        }
    })
    .catch(error => console.log('Error loading the data: ' + error));
}*/

// Initial load of the chart


