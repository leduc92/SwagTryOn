import Plugin from 'src/plugin-system/plugin.class';
import QRCode from 'qrcode';

export default class SwagTryOnQrcodePlugin extends Plugin {
    static options = {
        text: '',
        width: 240,
        errorCorrectionLevel: 'H',
        margin: 1,
    };

    init() {
        if (!this.options.text) {
            return;
        }

        QRCode.toCanvas(this.options.text, {
            width: this.options.width,
            errorCorrectionLevel: this.options.errorCorrectionLevel,
            margin: this.options.margin,
        }, (error, canvas) => {
            if (error || !canvas) {
                return;
            }

            canvas.setAttribute('aria-label', 'QR code for virtual try-on');
            canvas.style.display = 'block';
            this.el.replaceChildren(canvas);
        });
    }
}
