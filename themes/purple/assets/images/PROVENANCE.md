# Image provenance

All photographic placeholder images in this directory were generated with
Google Gemini by Ana Dovgal (Woo Creative & Brand studio) in June–July 2026,
replacing earlier Reve/Midjourney assets so the licensing chain is clean for
theme distribution. Original deliveries (with intact C2PA manifests) live in
the studio's Google Drive; see the studio request P2 for links and history:
https://woomarketingstudio.wordpress.com/2026/06/09/studio-request-purple-theme-placeholder-images/

## Machine-readable marking (EU AI Act §50(2) alignment)

The files listed below carry the IPTC digital source type marker
(`XMP-iptcExt:DigitalSourceType = trainedAlgorithmicMedia`), applied
2026-07-14 with exiftool after crop/WebP conversion stripped the original
metadata. Google's SynthID watermark additionally survives in the pixels of
every generated image. C2PA manifests cannot survive re-encoding by design;
the originals in Drive retain them.

To tag a newly added AI-generated image:

    exiftool -overwrite_original \
      "-XMP-iptcExt:DigitalSourceType=http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia" \
      <file.webp>

…and add it to the list below.

## AI-generated (tagged)

- blue-sweater-cut.webp
- blue-sweater-man.webp
- green-sweater-hands.webp
- green-sweater-woman.webp
- orange-sweater-woman-cropped-2.webp
- orange-sweater-woman-cropped.webp
- orange-sweater.webp
- pink-sweater-man-1.webp
- pink-sweater-man-cropped-2.webp
- pink-sweater-man-cropped.webp
- pink-sweater-woman.webp
- product-beanie-lavander-purple-theme-3.webp
- product-socks-gray-purple-theme-2.webp
- product-socks-yellow-purple-theme-1.webp
- storytelling-field.webp
- storytelling-goat.webp
- storytelling-machine.webp
- storytelling-store-2.webp
- storytelling-store.webp
- storytelling-wool.webp
- sweaters.webp

## Human-designed (not AI-generated, untagged)

- logo-1.webp, logo-2.webp, logo-3.webp
- icon-*.svg (all)
