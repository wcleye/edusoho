import StudentTrendency from './student-trendency';
import TaskDetail from './task-detail';
import FinishedRateTrend from './finished-rate-trend';

class CourseDashboard{
    constructor() {
        this.init();
        this.tabToggle();
        this.charts();
    }

    init(){
      new StudentTrendency();
      new FinishedRateTrend()
    }

    tabToggle(){
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            let $target = $(e.target);
            let $content = $($target.attr('href'));
            $content.trigger('init');
        })
    }

    charts(){
        this._taskDetailChart();
    }

    _taskDetailChart(){
        let self = this;
        let $taskDetail = $('#task-data-detail');
        let $taskChart = $('#task-data-chart');
        $taskDetail.on('init', function(){
            if (self.taskDetail) return;
            self.taskDetail = new TaskDetail($taskChart);
        })

        $taskDetail.on('click', '.pagination a', function(){
            let $this = $(this);
            let url = $this.attr('href');
            self.taskDetail.update(url);
            return false;
        });

        $taskDetail.find('input').bind('keypress',function(event){
            if (13 === event.keyCode) {
                let value = $(this).val();
                let url = $taskChart.data('url') + '?title=' + value;
                self.taskDetail.update(url);
            }
        });

        $('.js-task-detail-search').on('click',function(){
            let value = $(this).prev().val();
            let url = $taskChart.data('url') + '?title=' + value;
            self.taskDetail.update(url);
        })
    }
}

let courseDashboard = new CourseDashboard();