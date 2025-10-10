/* js/dashboard.js - Enhanced Dashboard JavaScript with Preview and Export Support */

(function($) {
  'use strict';

  var FileAnalyzer = {
    charts: {},
    selectedFiles: [],

    init: function() {
      this.initializeCharts();
      this.bindEvents();
      this.formatFileSizes();
      this.updateStatistics();
    },

    initializeCharts: function() {
      this.initTimelineChart();
      this.initFileTypeChart();
    },

    initTimelineChart: function() {
      var ctx = document.getElementById('timelineChart');
      if (!ctx) return;

      var fileData = FileAnalyzerData.fileData || {};
      var monthlyData = fileData.monthly || {};

      var labels = Object.keys(monthlyData).sort();
      var sizeData = labels.map(function(month) {
        return (monthlyData[month].size / (1024 * 1024)).toFixed(2);
      });
      var countData = labels.map(function(month) {
        return monthlyData[month].count;
      });

      var primaryColor = FileAnalyzerData.directoryType === 'contribute' ? '#764ba2' : '#667eea';
      var secondaryColor = FileAnalyzerData.directoryType === 'contribute' ? 'rgba(118, 75, 162, 0.1)' : 'rgba(102, 126, 234, 0.1)';

      this.charts.timeline = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Storage Size (MB)',
            data: sizeData,
            borderColor: primaryColor,
            backgroundColor: secondaryColor,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: primaryColor,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#ffffff',
              bodyColor: '#ffffff',
              borderColor: primaryColor,
              borderWidth: 1,
              cornerRadius: 6,
              displayColors: false,
              callbacks: {
                title: function(tooltipItems) {
                  return 'Month: ' + tooltipItems[0].label;
                },
                label: function(context) {
                  var month = context.label;
                  var data = monthlyData[month];
                  return [
                    'Size: ' + context.parsed.y + ' MB',
                    'Files: ' + data.count
                  ];
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                color: '#6b7280'
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              },
              ticks: {
                color: '#6b7280',
                callback: function(value) {
                  return value + ' MB';
                }
              }
            }
          },
          interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
          }
        }
      });
    },

    initFileTypeChart: function() {
      var ctx = document.getElementById('fileTypeChart');
      if (!ctx) return;

      var fileData = FileAnalyzerData.fileData || {};
      var typeData = fileData.fileTypes || {};

      var labels = Object.keys(typeData);
      var counts = labels.map(function(type) {
        return typeData[type].count;
      });
      var sizes = labels.map(function(type) {
        return typeData[type].size;
      });

      var colors = FileAnalyzerData.directoryType === 'contribute'
        ? ['#764ba2', '#667eea', '#f093fb', '#f5576c', '#4facfe', '#43e97b', '#fa709a', '#ffecd2']
        : ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#64748b', '#06b6d4', '#84cc16'];

      this.charts.fileType = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels.map(function(l) { return l.toUpperCase(); }),
          datasets: [{
            data: counts,
            backgroundColor: colors.slice(0, labels.length),
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverBorderWidth: 3,
            hoverOffset: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                padding: 20,
                usePointStyle: true,
                font: {
                  size: 12
                },
                generateLabels: function(chart) {
                  var data = chart.data;
                  return data.labels.map(function(label, i) {
                    var type = labels[i];
                    var count = counts[i];
                    var size = FileAnalyzer.formatBytes(sizes[i]);
                    return {
                      text: label + ' (' + count + ' files, ' + size + ')',
                      fillStyle: colors[i],
                      strokeStyle: colors[i],
                      pointStyle: 'circle'
                    };
                  });
                }
              }
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleColor: '#ffffff',
              bodyColor: '#ffffff',
              borderColor: FileAnalyzerData.directoryType === 'contribute' ? '#764ba2' : '#667eea',
              borderWidth: 1,
              cornerRadius: 6,
              displayColors: true,
              callbacks: {
                label: function(context) {
                  var type = labels[context.dataIndex];
                  var count = counts[context.dataIndex];
                  var size = FileAnalyzer.formatBytes(sizes[context.dataIndex]);
                  var percentage = ((count / counts.reduce(function(a, b) { return a + b; }, 0)) * 100).toFixed(1);
                  return [
                    type.toUpperCase() + ': ' + count + ' files',
                    'Size: ' + size,
                    'Percentage: ' + percentage + '%'
                  ];
                }
              }
            }
          },
          cutout: '50%'
        }
      });
    },

    bindEvents: function() {
      var self = this;

      // Refresh button
      $('#refreshBtn').on('click', function() {
        self.refreshData();
      });

      // Timeline chart metric selector
      $('#timelineMetric').on('change', function() {
        self.updateTimelineChart($(this).val());
      });
    },

    formatFileSizes: function() {
      $('.size-bytes').each(function() {
        var bytes = parseInt($(this).data('bytes'));
        $(this).text(FileAnalyzer.formatBytes(bytes));
      });
    },

    updateStatistics: function() {
      var stats = FileAnalyzerData.directoryStats || {};
      console.log(stats);
      var abandonedFiles = FileAnalyzerData.abandonedFiles || [];

      var totalAbandonedSize = abandonedFiles.reduce(function(sum, file) {
        return sum + file.size;
      }, 0);

      $('#totalFiles').text(this.formatNumber(stats.totalFiles || 0));
      $('#totalSize').text(this.formatBytes(stats.totalSize || 0));
      $('#abandonedCount').text(this.formatNumber(stats.abandonedFiles || 0));
      $('#wastedSpace').text(this.formatBytes(stats.abandonedSize || 0));
    },

    updateTimelineChart: function(metric) {
      if (!this.charts.timeline) return;

      var fileData = FileAnalyzerData.fileData || {};
      var monthlyData = fileData.monthly || {};
      var labels = Object.keys(monthlyData).sort();

      var data, label, color;
      var primaryColor = FileAnalyzerData.directoryType === 'contribute' ? '#764ba2' : '#667eea';

      if (metric === 'count') {
        data = labels.map(function(month) {
          return monthlyData[month].count;
        });
        label = 'File Count';
        color = '#10b981';
      } else {
        data = labels.map(function(month) {
          return (monthlyData[month].size / (1024 * 1024)).toFixed(2);
        });
        label = 'Storage Size (MB)';
        color = primaryColor;
      }

      this.charts.timeline.data.datasets[0].data = data;
      this.charts.timeline.data.datasets[0].label = label;
      this.charts.timeline.data.datasets[0].borderColor = color;
      this.charts.timeline.data.datasets[0].pointBackgroundColor = color;
      this.charts.timeline.data.datasets[0].backgroundColor = color.replace(')', ', 0.1)').replace('rgb', 'rgba');

      this.charts.timeline.options.scales.y.ticks.callback = function(value) {
        return metric === 'count' ? value : value + ' MB';
      };

      this.charts.timeline.update();
    },

    refreshData: function() {
      var $btn = $('#refreshBtn');
      var originalText = $btn.html();

      $btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> Refreshing...');

      // Simulate data refresh - in real implementation, this would make an AJAX call
      setTimeout(function() {
        location.reload();
      }, 1000);
    },

    formatBytes: function(bytes) {
      if (bytes === 0) return '0 Bytes';
      var k = 1024;
      var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
      var i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    formatNumber: function(num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  };

  window.refreshData = function() {
    FileAnalyzer.refreshData();
  };

  window.updateTimelineChart = function() {
    var metric = $('#timelineMetric').val();
    FileAnalyzer.updateTimelineChart(metric);
  };

  // Initialize when document is ready
  $(document).ready(function() {
    // Wait for charts to be available
    if (typeof Chart !== 'undefined') {
      FileAnalyzer.init();
    } else {
      // Retry after a short delay if Chart.js isn't loaded yet
      setTimeout(function() {
        if (typeof Chart !== 'undefined') {
          FileAnalyzer.init();
        }
      }, 500);
    }

    // Auto-refresh every 5 minutes
    setInterval(function() {
      if (document.visibilityState === 'visible') {
        // Only refresh if page is visible
        // FileAnalyzer.refreshData();
      }
    }, 300000); // 5 minutes

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
        // Page became visible, check for updates
      }
    });

    // Tooltip initialization for action buttons
    $('.action-buttons .button').each(function() {
      var title = $(this).attr('title');
      if (title) {
        $(this).tooltip({
          content: title,
          position: { my: "center bottom-20", at: "center top" }
        });
      }
    });

    // Progress indication for long operations
    $(document).ajaxStart(function() {
      $('body').addClass('loading');
    }).ajaxStop(function() {
      $('body').removeClass('loading');
    });
  });

  // Export FileAnalyzer for external use
  window.FileAnalyzer = FileAnalyzer;

})(CRM.$);
