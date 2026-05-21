import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static values = {
        type: String,
        data: Object,
        options: Object
    }

    connect() {
        this.render();
    }

    render() {
        if (this.chart) {
            this.chart.destroy();
        }

        this.chart = new Chart(this.element, {
            type: this.typeValue || 'line',
            data: this.dataValue,
            options: this.optionsValue || {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
        }
    }
}
