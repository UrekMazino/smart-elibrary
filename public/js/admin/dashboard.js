$(function() {
  'use strict';

  var colors = {
    primary        : "#6571ff",
    secondary      : "#7987a1",
    success        : "#05a34a",
    info           : "#66d1d1",
    warning        : "#fbbc06",
    danger         : "#ff3366",
    light          : "#e9ecef",
    dark           : "#060c17",
    muted          : "#7987a1",
    gridBorder     : "rgba(77, 138, 240, .15)",
    bodyColor      : "#000",
    cardBg         : "#fff"
  }

  var fontFamily = "'Roboto', Helvetica, sans-serif"



  // Website traffic — real per-month view counts (Guest vs Registered), lazy-loaded.
  // The data is fetched (and the chart rendered) only when it scrolls into view, so
  // it never adds to initial page-load cost. The endpoint itself is cached server-side.
  if ($('#numberOfViewsChart').length) {
    var viewsChartLoaded = false;

    var renderViewsChart = function () {
      if (viewsChartLoaded) return;
      viewsChartLoaded = true;

      $.ajax({
        type: 'GET',
        url: (typeof getViewsTrafficUrl !== 'undefined') ? getViewsTrafficUrl : '/admin/views-traffic/json',
        dataType: 'json',
        success: function (res) {
          var lineChartOptions = {
            chart: {
              type: "line",
              height: '320',
              parentHeightOffset: 0,
              foreColor: colors.bodyColor,
              background: colors.cardBg,
              toolbar: { show: false },
            },
            theme: { mode: 'light' },
            tooltip: { theme: 'light' },
            colors: [colors.primary, colors.warning],
            grid: {
              padding: { bottom: -4 },
              borderColor: colors.gridBorder,
              xaxis: { lines: { show: true } }
            },
            series: [
              { name: "Registered User", data: res.registered || [] },
              { name: "Guest", data: res.guest || [] }
            ],
            xaxis: {
              type: "category",
              categories: res.labels || [],
              axisBorder: { color: colors.gridBorder },
              axisTicks: { color: colors.gridBorder },
            },
            markers: { size: 3 },
            legend: {
              show: true,
              position: "top",
              horizontalAlign: 'center',
              fontFamily: fontFamily,
              itemMargin: { horizontal: 8, vertical: 0 },
            },
            stroke: { width: 3, curve: "smooth", lineCap: "round" },
            noData: { text: 'No view data yet.' }
          };
          var apexLineChart = new ApexCharts(document.querySelector("#numberOfViewsChart"), lineChartOptions);
          apexLineChart.render();
        },
        error: function () {
          $('#numberOfViewsChart').html('<p class="text-muted text-center mb-0 py-5">Unable to load traffic data.</p>');
        }
      });
    };

    // Lazy-load: render on first scroll-into-view; fall back to immediate render if unsupported.
    if ('IntersectionObserver' in window) {
      var viewsObserver = new IntersectionObserver(function (entries, obs) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) { renderViewsChart(); obs.disconnect(); }
        });
      }, { rootMargin: '150px' });
      viewsObserver.observe(document.querySelector('#numberOfViewsChart'));
    } else {
      renderViewsChart();
    }
  }
  // Website traffic end



  // Apex Bar chart start
  if ($('#aanrCollectionApexBar').length) 
  {
    var randomNumbers = [];
    
    for (var i = 0; i < 8; i++) {
      var randomNumber = Math.floor(Math.random() * 100); // Generates a random number between 0 and 99
      randomNumbers.push(randomNumber);
    }

    var options = {
      chart: {
        type: 'bar',
        height: '320',
        parentHeightOffset: 0,
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: {
          show: false
        },
      },
      theme: {
        mode: 'light'
      },
      tooltip: {
        theme: 'light'
      },
      colors: [colors.primary],    
      grid: {
        padding: {
          bottom: -4
        },
        borderColor: colors.gridBorder,
        xaxis: {
          lines: {
            show: true
          }
        }
      },
      series: [{
        name: 'AANR material',
        data: randomNumbers
      }],
      xaxis: {
        type: 'text',
        categories: ['CORCARRD', 'CLAARRDEC', 'MAARRDEC', 'CVAARRDEC', 'VICARP', 'NOMCAARRD', 'SOXAARRDEC', 'CCAARRD'],
        axisBorder: {
          color: colors.gridBorder,
        },
        axisTicks: {
          color: colors.gridBorder,
        },
      },
      legend: {
        show: true,
        position: "top",
        horizontalAlign: 'center',
        fontFamily: fontFamily,
        itemMargin: {
          horizontal: 8,
          vertical: 0
        },
      },
      stroke: {
        width: 0
      },
      plotOptions: {
        bar: {
          borderRadius: 4
        }
      }
    }
    
    var apexBarChartCatalog = new ApexCharts(document.querySelector("#aanrCollectionApexBar"), options);
    apexBarChartCatalog.render();
  }
  // Apex Bar chart end

  $(document).ready(function() {
    $('#date-range').on('change', function() {
      let dates = $(this).val();
      let newStart;
      let newEnd;
      
      //This checks "dates" value either single date or range date 
      if(dates.split(' - ').length == 1){
    
        let selectedTime =  new Date(dates);
        // Set date time to midnight
        selectedTime.setHours(0,0,0,0);

        // Calculate end time of the day (i.e., just before midnight)
        let endTime = new Date(selectedTime.getTime() + 24 * 60 * 60 * 1000 - 1);

        // Convert the date to a UTC string
        let utcStringStart = selectedTime.toISOString();
        let utcStringEnd = endTime.toISOString();

        // Extract the date and time components
        let dateStringStart = utcStringStart.slice(0, 10);
        let timeStringStart = utcStringStart.slice(11, 19);

        let dateStringEnd = utcStringEnd.slice(0, 10);
        let timeStringEnd = utcStringEnd.slice(11, 19);

        // Format the date and time components as needed
        newStart = dateStringStart + ' ' + timeStringStart;
        newEnd = dateStringEnd + ' ' + timeStringEnd;
      } else {
        let startDate = dates.split(' - ')[0];  // return 2018-10-21
        let startArray = startDate.split('/');
        newStart = startArray[2] + '-' + startArray[0] + '-' + startArray[1];

        let endDate = dates.split(' - ')[1];    // return 2018-10-23
        let endArray = endDate.split('/');
        newEnd = endArray[2] + '-' + endArray[0] + '-' + endArray[1];
      }
      $.ajax({ 
              type: 'GET', 
              url: countDynamicDates, 
              data: { filter: 'dates', start: newStart, end: newEnd}, 
              dataType: 'json',
              async:false,
              success: function (res) {// HOLDINGS
                  if(res.numHoldings.length) {
                    $(".titleTotal").html(0);
                    $.each(res.numHoldings, function(index, e) {
                      $(".titleTotal#title-" + e.material_type_id + "-id").html(e.num_holdings.toLocaleString('en-US', {maximumFractionDigits: 2}));
                    });
                  } else {
                    $(".titleTotal").html(0);
                  }

                  if(res.numHoldingsDownloads.length) {
                    $(".downloadTitleTotal").html(0);
                    $.each(res.numHoldingsDownloads, function(index, e) {
                      $(".downloadTitleTotal#download-title-" + e.material_type_id + "-id").html(e.num_downloads.toLocaleString('en-US', {maximumFractionDigits: 2}));
                    });
                  } else {
                    $(".downloadTitleTotal").html(0);
                  }

                  // PATRONS
                  if(res.numPatrons.length) {
                    $(".patronTotal").html(0);
                    $.each(res.numPatrons, function(index, e) {
                      $(".patronTotal#patron-" + e.patrontypes_id + "-id").html(e.num_users.toLocaleString('en-US', {maximumFractionDigits: 2}));
                    });
                  } else {
                    $(".patronTotal").html(0);
                  }

                  if(res.numPatronsDownloads.length) {
                    $(".downloadPatronTotal").html(0);
                    $.each(res.numPatronsDownloads, function(index, e) {
                      $(".downloadPatronTotal#download-patron-" + e.patrontypes_id + "-id").html(e.num_downloads.toLocaleString('en-US', {maximumFractionDigits: 2}));
                    });
                  } else {
                    $(".downloadPatronTotal").html(0);
                  }

                  const patronCount = +res.patron;
                  const downloadCount = +res.download;
                  const titleCount = +res.title;
                  const publicationCount = +res.publication;

                  const patronFormatted = patronCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const downloadFormatted = downloadCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const titleFormatted = titleCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const publicationFormatted = publicationCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  $('.patron-count-view').html(patronFormatted);
                  $('.download-count-view').html(downloadFormatted);
                  $('.publication-count-view').html(publicationFormatted);
                  $('.titles-count-view').html(titleFormatted);   
              }
          });
    });

    $('.dynamic-count').click(function() {
      let id = $(this).attr('id');
        if (!$(this).hasClass('btn-primary')) {
          const $parent = $(this).parent();

          // get all the siblings of the parent element
          const $siblings = $parent.children().not($(this));

          // check if any of the siblings have the class you are searching for
          const classToSearch = 'btn-primary';
          const $siblingsWithClass = $siblings.filter(`.${classToSearch}`);

          // remove the class from the siblings that have it
          $siblingsWithClass.removeClass(classToSearch);
          $siblingsWithClass.addClass('btn-outline-primary');
          
          $(this).addClass('btn-primary');
          $(this).removeClass('btn-outline-primary');
        }
          $.ajax({ 
              type: 'GET', 
              url: countDynamicDates, 
              data: { filter: id }, 
              dataType: 'json',
              async:false,
              success: function (res) {
                // HOLDINGS
                if(res.numHoldings.length) {
                  $(".titleTotal").html(0);
                  $.each(res.numHoldings, function(index, e) {
                    $(".titleTotal#title-" + e.material_type_id + "-id").html(e.num_holdings.toLocaleString('en-US', {maximumFractionDigits: 2}));
                  });
                } else {
                  $(".titleTotal").html(0);
                }

                if(res.numHoldingsDownloads.length) {
                  $(".downloadTitleTotal").html(0);
                  $.each(res.numHoldingsDownloads, function(index, e) {
                    $(".downloadTitleTotal#download-title-" + e.material_type_id + "-id").html(e.num_downloads.toLocaleString('en-US', {maximumFractionDigits: 2}));
                  });
                } else {
                  $(".downloadTitleTotal").html(0);
                }

                // PATRONS
                if(res.numPatrons.length) {
                  $(".patronTotal").html(0);
                  $.each(res.numPatrons, function(index, e) {
                    $(".patronTotal#patron-" + e.patrontypes_id + "-id").html(e.num_users.toLocaleString('en-US', {maximumFractionDigits: 2}));
                  });
                } else {
                  $(".patronTotal").html(0);
                }

                if(res.numPatronsDownloads.length) {
                  $(".downloadPatronTotal").html(0);
                  $.each(res.numPatronsDownloads, function(index, e) {
                    $(".downloadPatronTotal#download-patron-" + e.patrontypes_id + "-id").html(e.num_downloads.toLocaleString('en-US', {maximumFractionDigits: 2}));
                  });
                } else {
                  $(".downloadPatronTotal").html(0);
                }

                  const patronCount = +res.patron;
                  const downloadCount = +res.download;
                  const titleCount = +res.title;
                  const publicationCount = +res.publication;

                  const patronFormatted = patronCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const downloadFormatted = downloadCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const titleFormatted = titleCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  const publicationFormatted = publicationCount.toLocaleString('en-US', {maximumFractionDigits: 2});
                  $('.patron-count-view').html(patronFormatted);
                  $('.download-count-view').html(downloadFormatted);
                  $('.publication-count-view').html(publicationFormatted);
                  $('.titles-count-view').html(titleFormatted); 
              }
          });
    });

  });

  // Title Chart
  if($('#titlesChart').length) {
    let titleDatesArr;
    let titleTotalPerDateArr;
    let selectedDate;
    $(document).on('click', '.dynamic-count#year, .dynamic-count#total', function() {
      let selectedDate = 'years';
      let chart = new ApexCharts(document.querySelector("#titlesChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'titleChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#title-no-record").html('<h6>No new title/s these last years.</h6>');
            $("#title-no-record").attr('class', 'text-center');
          } else {
            $("#title-no-record").html("");
          }

          titleDatesArr = res[0];
          titleTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            name: 'Total',
            data: titleTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: titleDatesArr
            }
          });
        },
        error: function() {
          chart.showNoData();
        }
      });
        chart.render();
    });

    $(document).on('click', '.dynamic-count#month', function() {
      let selectedDate = 'months';
      let chart = new ApexCharts(document.querySelector("#titlesChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'month',
          categories: []
        },
      });
      
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'titleChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#title-no-record").html('<h6>No new title/s these months.</h6>');
            $("#title-no-record").attr('class', 'text-center');
          } else {
            $("#title-no-record").html("");
          }
          titleDatesArr = res[0];
          titleTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: titleTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: titleDatesArr
            }
          });
        },
        error: function() {
              //Show error here 
        }
      });
      chart.render();
    });

    $(document).on('click', '.dynamic-count#today', function() {
      let selectedDate = 'days';
      let chart = new ApexCharts(document.querySelector("#titlesChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'day',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'titleChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#title-no-record").html('<h6>No new title/s this week.</h6>');
            $("#title-no-record").attr('class', 'text-center');
          } else {
            $("#title-no-record").html("");
          }
          titleDatesArr = res[0];
          titleTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: titleTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: titleDatesArr
            }
          });
        },
        error: function() {
        }
      });
      chart.render();
    });

    $(document).ready(function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#titlesChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'titleChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          let titleDatesArr = res[0];
          let titleTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: titleTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: titleDatesArr
            }
          });
          // chart.hideLoading();
        },
        error: function() {
          // Show error here
        }
      });
    });


  }
  // Title Chart - END
  // Patron Chart
  if($('#patronsChart').length) {
    let patronDatesArr;
    let patronTotalPerDateArr;
    let selectedDate;
    $(document).on('click', '.dynamic-count#year, .dynamic-count#total', function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#patronsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'patronsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#patron-no-record").html('<h6>No new patron/s these last years.</h6>');
            $("#patron-no-record").attr('class', 'text-center');
          } else {
            $("#patron-no-record").html("");
          }
          let patronDatesArr = res[0];
          let patronTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: patronTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: patronDatesArr
            }
          });
        },
        error: function() {
        }
      });
        chart.render();
    });

    $(document).on('click', '.dynamic-count#month', function() {
      let selectedDate = 'months'
      let chart = new ApexCharts(document.querySelector("#patronsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'month',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'patronsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#patron-no-record").html('<h6>No new patron/s these last months.</h6>');
            $("#patron-no-record").attr('class', 'text-center');
          } else {
            $("#patron-no-record").html("");
          }
          let patronDatesArr = res[0];
          let patronTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: patronTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: patronDatesArr
            }
          });
        },
        error: function() {
        }
      });
        chart.render();
    });

    $(document).on('click', '.dynamic-count#today', function() {
      let selectedDate = 'days'
      let chart = new ApexCharts(document.querySelector("#patronsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'day',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'patronsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#patron-no-record").html('<h6>No new patron/s this week.</h6>');
            $("#patron-no-record").attr('class', 'text-center');
          } else {
            $("#patron-no-record").html("");
          }
          let patronDatesArr = res[0];
          let patronTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: patronTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: patronDatesArr
            }
          });
        },
        error: function() {
        }
      });
      chart.render();
    });

    $(document).ready(function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#patronsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'patronsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#patron-no-record").html('<h6>No new patron/s these last years.</h6>');
            $("#patron-no-record").attr('class', 'text-center');
          } else {
            $("#patron-no-record").html("");
          }
          let patronDatesArr = res[0];
          let patronTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: patronTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: patronDatesArr
            }
          });
        },
        error: function() {
          // chart.hideLoading();
          chart.showNoData();
        }
      });
      chart.render();
    });

  }
  // Patron Chart - END
  // Downloads Chart
  if($('#downloadsChart').length) {
    let downloadDatesArr;
    let downloadTotalPerDateArr;
    let selectedDate;
    $(document).on('click', '.dynamic-count#year, .dynamic-count#total', function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#downloadsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'downloadsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#download-no-record").html('<h6>No new download/s these last years.</h6>');
            $("#download-no-record").attr('class', 'text-center');
          } else {
            $("#download-no-record").html("");
          }
          let downloadDatesArr = res[0];
          let downloadTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: downloadTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: downloadDatesArr
            }
          });
        },
        error: function() {

        }
      });
      chart.render();
    });

    $(document).on('click', '.dynamic-count#month', function() {
      let selectedDate = 'months'
      let chart = new ApexCharts(document.querySelector("#downloadsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'month',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'downloadsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#download-no-record").html('<h6>No new download/s these months.</h6>');
            $("#download-no-record").attr('class', 'text-center');
          } else {
            $("#download-no-record").html("");
          }
          let downloadDatesArr = res[0];
          let downloadTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: downloadTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: downloadDatesArr
            }
          });
        },
        error: function() {

        }
      });
      chart.render();
    });

    $(document).on('click', '.dynamic-count#today', function() {
      let selectedDate = 'days'
      let chart = new ApexCharts(document.querySelector("#downloadsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'day',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'downloadsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#download-no-record").html('<h6>No new download/s this week.</h6>');
            $("#download-no-record").attr('class', 'text-center');
          } else {
            $("#download-no-record").html("");
          }
          let downloadDatesArr = res[0];
          let downloadTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: downloadTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: downloadDatesArr
            }
          });
        },
        error: function() {
        }
      });
      chart.render();
    });

    $(document).ready(function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#downloadsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'downloadsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#download-no-record").html('<h6>No new download/s these last years.</h6>');
            $("#download-no-record").attr('class', 'text-center');
          } else {
            $("#download-no-record").html("");
          }
          let downloadDatesArr = res[0];
          let downloadTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: downloadTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: downloadDatesArr
            }
          });
        },
        error: function() {

        }
      });
      chart.render();
    });
  }
  // Downloads Chart - END
  // New Publications Chart
    if($('#publicationsChart').length) {
    let publicationDatesArr;
    let publicationTotalPerDateArr;
    let selectedDate;
    $(document).on('click', '.dynamic-count#year, .dynamic-count#total', function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#publicationsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'publicationsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#publication-no-record").html('<h6>No new publication/s these last years.</h6>');
            $("#publication-no-record").attr('class', 'text-center');
          } else {
            $("#publication-no-record").html("");
          }
          let publicationDatesArr = res[0];
          let publicationTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: publicationTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: publicationDatesArr
            }
          });
        },
        error: function() {

        }
      });
      chart.render();
    });

    $(document).on('click', '.dynamic-count#month', function() {
      let selectedDate = 'months'
      let chart = new ApexCharts(document.querySelector("#publicationsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'month',
          categories: []
        },
      });
      chart.render();
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'publicationsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#publication-no-record").html('<h6>No new publication/s these last months.</h6>');
            $("#publication-no-record").attr('class', 'text-center');
          } else {
            $("#publication-no-record").html("");
          }
          let publicationDatesArr = res[0];
          let publicationTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: publicationTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: publicationDatesArr
            }
          });
        },
        error: function() {
          chart.showNoData();
        }
      });
        chart.render();
    });

    $(document).on('click', '.dynamic-count#today', function() {
      let selectedDate = 'days';
      let chart = new ApexCharts(document.querySelector("#publicationsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'day',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'publicationsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#publication-no-record").html('<h6>No new publication/s this week.</h6>');
            $("#publication-no-record").attr('class', 'text-center');
          } else {
            $("#publication-no-record").html("");
          }
          let publicationDatesArr = res[0];
          let publicationTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: publicationTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: publicationDatesArr
            }
          });
        },
        error: function(e) {
        }
      });
      chart.render();
    });

    $(document).ready(function() {
      let selectedDate = 'years'
      let chart = new ApexCharts(document.querySelector("#publicationsChart"), {
        chart: {
          type: "bar",
          height: 60,
          sparkline: {
            enabled: !0
          }
        },
        plotOptions: {
          bar: {
            borderRadius: 2,
            columnWidth: "60%"
          }
        },
        colors: [colors.primary],
        series: [{
          name: '',
          data: []
        }],
        xaxis: {
          type: 'year',
          categories: []
        },
      });
      chart.render();
    
      $.ajax({ 
        type: 'GET', 
        url: getPerDateChartUrl,
        data: { filter: 'publicationsChart', selection: selectedDate }, 
        dataType: 'json',
        success: function (res) {
          if(!res[0].length){
            $("#publication-no-record").html('<h6>No new publication/s these last years.</h6>');
            $("#publication-no-record").attr('class', 'text-center');
          } else {
            $("#publication-no-record").html("");
          }
          let publicationDatesArr = res[0];
          let publicationTotalPerDateArr = res[1];
          // Update chart data and categories
          chart.updateSeries([{
            data: publicationTotalPerDateArr
          }]);
          chart.updateOptions({
            xaxis: {
              categories: publicationDatesArr
            }
          });
        },
        error: function() {

        }
      });
      chart.render();
    });
  }
  // New Publications Chart - END

  // Responsiveness chart start
  if ($('#responsivenessChart').length) {

    let countArr = [0,0,0,0,0];
    let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
    let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
    $.ajax({ 
        type: 'GET', 
        url: getRatingsUrl, 
        data: { rating: 'rating1' }, 
        dataType: 'json',
        async:false,
        success: function (res) { 
            $.each(res, function(index, e) {
              countArr[e.rating1-1] = e.rating_sum;
            });
        }
    });
    var options = {
      chart: {
        height: 300,
        type: "donut",
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: {
          show: false
        },
      },
      theme: {
        mode: 'light'
      },
      tooltip: {
        theme: 'light'
      },
      stroke: {
        colors: ['rgba(0,0,0,0)']
      },
      colors: colorVal,
      legend: {
        show: true,
        position: "top",
        horizontalAlign: 'center',
        fontFamily: fontFamily,
        itemMargin: {
          horizontal: 8,
          vertical: 0
        },
      },
      dataLabels: {
        enabled: false
      },
        series: countArr,
        labels: labelVal
    };

    var chart = new ApexCharts(document.querySelector("#responsivenessChart"), options);
    chart.render();
  }
  // Responsiveness chart end

  // Reliability chart start
  if ($('#reliabilityChart').length) {

    let countArr = [0,0,0,0,0];
    let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
    let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
    $.ajax({ 
        type: 'GET', 
        url: getRatingsUrl, 
        data: { rating: 'rating2' }, 
        dataType: 'json',
        async:false,
        success: function (res) {
            $.each(res, function(index, e) {
              countArr[e.rating2-1] = e.rating_sum;
            });
        }
    });
    var options = {
      chart: {
        height: 300,
        type: "donut",
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: {
          show: false
        },
      },
      theme: {
        mode: 'light'
      },
      tooltip: {
        theme: 'light'
      },
      stroke: {
        colors: ['rgba(0,0,0,0)']
      },
      colors: colorVal,
      legend: {
        show: true,
        position: "top",
        horizontalAlign: 'center',
        fontFamily: fontFamily,
        itemMargin: {
          horizontal: 8,
          vertical: 0
        },
      },
      dataLabels: {
        enabled: false
      },
        series: countArr,
        labels: labelVal
    };

    var chart = new ApexCharts(document.querySelector("#reliabilityChart"), options);
    chart.render();
  }
  // Reliability chart end

  // Access & Facilities chart start
  if ($('#accessAndFacilitiesChart').length) {

    let countArr = [0,0,0,0,0];
    let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
    let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
    $.ajax({ 
        type: 'GET', 
        url: getRatingsUrl, 
        data: { rating: 'rating3' }, 
        dataType: 'json',
        async:false,
        success: function (res) {
            $.each(res, function(index, e) {
              countArr[e.rating3-1] = e.rating_sum;
            });
        }
    });
    var options = {
      chart: {
        height: 300,
        type: "donut",
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: {
          show: false
        },
      },
      theme: {
        mode: 'light'
      },
      tooltip: {
        theme: 'light'
      },
      stroke: {
        colors: ['rgba(0,0,0,0)']
      },
      colors: colorVal,
      legend: {
        show: true,
        position: "top",
        horizontalAlign: 'center',
        fontFamily: fontFamily,
        itemMargin: {
          horizontal: 8,
          vertical: 0
        },
      },
      dataLabels: {
        enabled: false
      },
        series: countArr,
        labels: labelVal
    };

    var chart = new ApexCharts(document.querySelector("#accessAndFacilitiesChart"), options);
    chart.render();
  }
  // Access & Facilities chart end

  // Communication chart start
  if ($('#communicationChart').length) {

    let countArr = [0,0,0,0,0];
    let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
    let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
    $.ajax({ 
        type: 'GET', 
        url: getRatingsUrl, 
        data: { rating: 'rating4' }, 
        dataType: 'json',
        async:false,
        success: function (res) {
            $.each(res, function(index, e) {
              countArr[e.rating4-1] = e.rating_sum;
            });
        }
    });
    var options = {
      chart: {
        height: 300,
        type: "donut",
        foreColor: colors.bodyColor,
        background: colors.cardBg,
        toolbar: {
          show: false
        },
      },
      theme: {
        mode: 'light'
      },
      tooltip: {
        theme: 'light'
      },
      stroke: {
        colors: ['rgba(0,0,0,0)']
      },
      colors: colorVal,
      legend: {
        show: true,
        position: "top",
        horizontalAlign: 'center',
        fontFamily: fontFamily,
        itemMargin: {
          horizontal: 8,
          vertical: 0
        },
      },
      dataLabels: {
        enabled: false
      },
        series: countArr,
        labels: labelVal
    };

    var chart = new ApexCharts(document.querySelector("#communicationChart"), options);
    chart.render();
  }
  // Communication chart end

    // Integrity chart start
    if ($('#integrityChart').length) {

      let countArr = [0,0,0,0,0];
      let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
      let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
      $.ajax({ 
          type: 'GET', 
          url: getRatingsUrl, 
          data: { rating: 'rating5' }, 
          dataType: 'json',
          async:false,
          success: function (res) {
              $.each(res, function(index, e) {
                countArr[e.rating5-1] = e.rating_sum;
              });
          }
      });
      var options = {
        chart: {
          height: 300,
          type: "donut",
          foreColor: colors.bodyColor,
          background: colors.cardBg,
          toolbar: {
            show: false
          },
        },
        theme: {
          mode: 'light'
        },
        tooltip: {
          theme: 'light'
        },
        stroke: {
          colors: ['rgba(0,0,0,0)']
        },
        colors: colorVal,
        legend: {
          show: true,
          position: "top",
          horizontalAlign: 'center',
          fontFamily: fontFamily,
          itemMargin: {
            horizontal: 8,
            vertical: 0
          },
        },
        dataLabels: {
          enabled: false
        },
          series: countArr,
          labels: labelVal
      };
  
      var chart = new ApexCharts(document.querySelector("#integrityChart"), options);
      chart.render();
    }
    // Integrity chart end

    // Assurance chart start
    if ($('#assuranceChart').length) {

      let countArr = [0,0,0,0,0];
      let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
      let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];
      $.ajax({ 
          type: 'GET', 
          url: getRatingsUrl, 
          data: { rating: 'rating6' }, 
          dataType: 'json',
          async:false,
          success: function (res) {
              $.each(res, function(index, e) {
                countArr[e.rating6-1] = e.rating_sum;
              });
          }
      });
      var options = {
        chart: {
          height: 300,
          type: "donut",
          foreColor: colors.bodyColor,
          background: colors.cardBg,
          toolbar: {
            show: false
          },
        },
        theme: {
          mode: 'light'
        },
        tooltip: {
          theme: 'light'
        },
        stroke: {
          colors: ['rgba(0,0,0,0)']
        },
        colors: colorVal,
        legend: {
          show: true,
          position: "top",
          horizontalAlign: 'center',
          fontFamily: fontFamily,
          itemMargin: {
            horizontal: 8,
            vertical: 0
          },
        },
        dataLabels: {
          enabled: false
        },
          series: countArr,
          labels: labelVal
      };
  
      var chart = new ApexCharts(document.querySelector("#assuranceChart"), options);
      chart.render();
    }
    // Assurance chart end

    // Outcome chart start
    if ($('#outcomeChart').length) {

      let countArr = [0,0,0,0,0];
      let labelVal = ["Poor", "Unsatisfactory", "Satisfactory", "Very Satisfactory", "Outstanding"];
      let colorVal = [colors.secondary, colors.danger, colors.warning, colors.info, colors.primary];

      $.ajax({ 
          type: 'GET', 
          url: getRatingsUrl, 
          data: { rating: 'rating7' }, 
          dataType: 'json',
          async:false,
          success: function (res) {
              $.each(res, function(index, e) {
                countArr[e.rating7-1] = e.rating_sum;
              });
          }
      });
      var options = {
        chart: {
          height: 300,
          type: "donut",
          foreColor: colors.bodyColor,
          background: colors.cardBg,
          toolbar: {
            show: false
          },
        },
        theme: {
          mode: 'light'
        },
        tooltip: {
          theme: 'light'
        },
        stroke: {
          colors: ['rgba(0,0,0,0)']
        },
        colors: colorVal,
        legend: {
          show: true,
          position: "top",
          horizontalAlign: 'center',
          fontFamily: fontFamily,
          itemMargin: {
            horizontal: 8,
            vertical: 0
          },
        },
        dataLabels: {
          enabled: false
        },
          series: countArr,
          labels: labelVal
      };
  
      var chart = new ApexCharts(document.querySelector("#outcomeChart"), options);
      chart.render();
    }
    // Outcome chart end
});
