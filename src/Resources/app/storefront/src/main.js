import SwagTryOnQrcodePlugin from './swag-try-on/qrcode.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('SwagTryOnQrcode', SwagTryOnQrcodePlugin, '[data-swag-try-on-qrcode]');
