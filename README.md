# SwagTryOn

Mobile-first virtual try-on plugin scaffold for Shopware 6.

## What it does

- Adds product custom fields so a merchant can enable try-on per product.
- Injects a "Virtual try-on" entry point into the storefront buy box.
- Generates a mobile landing page at `/try-on/{productId}` for camera-based preview.
- Uses MediaPipe hand landmarks in the browser to anchor a watch or ring overlay.
- Renders the QR code locally in the storefront instead of calling an external QR API.

## Product setup

For each try-on capable product:

1. Enable `Virtual try-on`.
2. Pick the wear mode: `watch` or `ring`.
3. Upload a transparent PNG somewhere public and set it as `Overlay asset URL`.
   If you leave this empty, the product cover image is used as a fallback.
4. Fine-tune `Overlay scale`, `Horizontal offset`, and `Vertical offset`.

## Plugin config

The plugin config lets you control:

- global enable/disable
- MediaPipe bundle URL
- MediaPipe WASM root
- Hand Landmarker model URL

## Notes

- This is an MVP storefront implementation. High-end AR occlusion and physically correct 3D placement will need device-specific tuning and usually a dedicated 3D asset pipeline.
- iOS and Android browsers behave differently for camera permissions and performance, so test on real devices early.
