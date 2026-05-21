import { Controller } from '@hotwired/stimulus';

// Defensive import for qr-code-styling
import QRCodeStylingLib from 'qr-code-styling';
const QRCodeStyling = QRCodeStylingLib.default || QRCodeStylingLib;

export default class extends Controller {
    static values = {
        data: String,
        logo: String,
        width: { type: Number, default: 200 },
        height: { type: Number, default: 200 }
    }

    connect() {
        // Use a small timeout to ensure DOM is ready and avoid race conditions
        setTimeout(() => {
            this.render();
        }, 50);
    }

    render() {
        if (!this.dataValue) {
            this.element.innerHTML = '<span class="text-[8px]">No Data</span>';
            return;
        }

        const width = this.widthValue || 200;
        const height = this.heightValue || 200;

        try {
            this.qrCode = new QRCodeStyling({
                width: width,
                height: height,
                type: "canvas",
                data: this.dataValue,
                image: this.logoValue || null,
                dotsOptions: {
                    color: "#094021",
                    type: "rounded"
                },
                backgroundOptions: {
                    color: "#ffffff",
                },
                imageOptions: {
                    crossOrigin: "anonymous",
                    margin: 2,
                    imageSize: 0.5
                },
                cornersSquareOptions: {
                    type: "extra-rounded",
                    color: "#094021"
                },
                cornersDotOptions: {
                    type: "dot",
                    color: "#03A64A"
                },
                qrOptions: {
                    errorCorrectionLevel: "H"
                }
            });

            this.element.innerHTML = '';
            this.qrCode.append(this.element);

            const canvas = this.element.querySelector('canvas');
            if (canvas) {
                canvas.style.maxWidth = '100%';
                canvas.style.height = 'auto';
                canvas.style.display = 'block';
            }
        } catch (e) {
            console.error('QR Render Error:', e);
            this.element.innerHTML = '<span class="text-[8px]">Error</span>';
        }
    }

    dataValueChanged() {
        if (this.qrCode) {
            this.qrCode.update({
                data: this.dataValue
            });
        }
    }
}
