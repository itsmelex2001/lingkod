using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class CreatePerfectBlinkSprites {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        int capBaseX = 132, capBaseY = 64;
        int cap29X = 595, cap29Y = 117;
        int cap30X = 598, cap30Y = 121;

        int dx29 = cap29X - capBaseX;
        int dy29 = cap29Y - capBaseY;
        int dx30 = cap30X - capBaseX;
        int dy30 = cap30Y - capBaseY;

        Bitmap girlHalf = new Bitmap(girl);
        Bitmap girlClosed = new Bitmap(girl);

        // 1. Build girlClosed:
        // Eye sockets to clear of open sclera/pupil:
        // Left: gx 56..132, gy 212..284
        // Right: gx 154..228, gy 212..280
        ClearAndSkinEye(girlClosed, 54, 134, 212, 284);
        ClearAndSkinEye(girlClosed, 150, 230, 212, 280);

        // Overlay authentic closed lashes & creases from s30:
        OverlayClosedLashes(girlClosed, s30, 54, 134, 212, 284, dx30, dy30);
        OverlayClosedLashes(girlClosed, s30, 150, 230, 212, 280, dx30, dy30);

        // 2. Build girlHalf:
        // Left: gx 54..134, gy 212..284
        // Right: gx 150..230, gy 212..280
        BlendHalfEye(girlHalf, girl, s29, 54, 134, 212, 284, dx29, dy29);
        BlendHalfEye(girlHalf, girl, s29, 150, 230, 212, 280, dx29, dy29);

        // Save crops for inspection
        SaveFaceCrop(girl, Path.Combine(assetsDir, "perfect_face_0_open.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "perfect_face_1_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "perfect_face_2_closed.png"));

        Console.WriteLine("Saved perfect_face_0..2.png!");
    }

    static void ClearAndSkinEye(Bitmap target, int minX, int maxX, int minY, int maxY) {
        Color baseSkin = Color.FromArgb(239, 202, 170);
        Color blushSkin = Color.FromArgb(240, 195, 172);

        for (int y = minY; y <= maxY; y++) {
            double t = (double)(y - minY) / (maxY - minY);
            int sr = (int)(baseSkin.R * (1 - t) + blushSkin.R * t);
            int sg = (int)(baseSkin.G * (1 - t) + blushSkin.G * t);
            int sb = (int)(baseSkin.B * (1 - t) + blushSkin.B * t);
            Color skinAtY = Color.FromArgb(255, sr, sg, sb);

            for (int x = minX; x <= maxX; x++) {
                Color c = target.GetPixel(x, y);
                // Don't overwrite side hair if black
                if (x < 56 && c.R < 60 && c.G < 60 && c.B < 60) continue;
                if (x > 224 && c.R < 60 && c.G < 60 && c.B < 60) continue;

                // Is pixel open eye (sclera or iris)?
                bool isSclera = (c.R > 210 && c.G > 210 && c.B > 210);
                bool isIris = (c.R < 130 && c.G < 130 && c.B < 140) || (c.B > c.R + 5);
                if (isSclera || isIris) {
                    target.SetPixel(x, y, skinAtY);
                }
            }
        }
    }

    static void OverlayClosedLashes(Bitmap target, Bitmap s30, int minX, int maxX, int minY, int maxY, int dx, int dy) {
        for (int y = minY; y <= maxY; y++) {
            int sy = y + dy;
            if (sy < 0 || sy >= s30.Height) continue;

            for (int x = minX; x <= maxX; x++) {
                int sx = x + dx;
                if (sx < 0 || sx >= s30.Width) continue;

                Color c = s30.GetPixel(sx, sy);
                // Closed lash: dark black/brown (R < 90 && G < 90 && B < 90)
                // Crease: eyelid crease (R < 165 && G < 130 && B < 120 && R - G > 20)
                bool isLash = (c.R < 90 && c.G < 90 && c.B < 90);
                bool isCrease = (c.R < 165 && c.G < 130 && c.B < 120 && (c.R - c.G) > 20);

                if (isLash || isCrease) {
                    target.SetPixel(x, y, c);
                }
            }
        }
    }

    static void BlendHalfEye(Bitmap target, Bitmap baseGirl, Bitmap s29, int minX, int maxX, int minY, int maxY, int dx, int dy) {
        // In s29, the top half of the eye is covered by the drooping eyelid, and the lower half has iris/sclera
        for (int y = minY; y <= maxY; y++) {
            int sy = y + dy;
            if (sy < 0 || sy >= s29.Height) continue;

            for (int x = minX; x <= maxX; x++) {
                int sx = x + dx;
                if (sx < 0 || sx >= s29.Width) continue;

                Color cSrc = s29.GetPixel(sx, sy);
                Color cBase = baseGirl.GetPixel(x, y);

                // If pixel is drooping lash line in s29:
                bool isLash29 = (cSrc.R < 90 && cSrc.G < 90 && cSrc.B < 90);
                // If pixel is drooping crease in s29:
                bool isCrease29 = (cSrc.R < 165 && cSrc.G < 130 && cSrc.B < 120 && (cSrc.R - cSrc.G) > 20);

                // If pixel in baseGirl was sclera/pupil:
                bool wasEyeInBase = (cBase.R > 210 && cBase.G > 210 && cBase.B > 210) || 
                                    (cBase.R < 130 && cBase.G < 130 && cBase.B < 140) || (cBase.B > cBase.R + 5);

                if (isLash29 || isCrease29) {
                    target.SetPixel(x, y, cSrc);
                } else if (wasEyeInBase) {
                    // If in s29 this pixel is skin (drooping eyelid covering upper eye):
                    bool isSkinIn29 = (cSrc.R > 180 && cSrc.G > 150 && cSrc.B > 120);
                    if (isSkinIn29) {
                        // Fill with base skin tone (not yellowish video skin)
                        double t = (double)(y - minY) / (maxY - minY);
                        int sr = (int)(239 * (1 - t) + 240 * t);
                        int sg = (int)(202 * (1 - t) + 195 * t);
                        int sb = (int)(170 * (1 - t) + 172 * t);
                        target.SetPixel(x, y, Color.FromArgb(255, sr, sg, sb));
                    } else {
                        // Lower half of eye: copy pupil/sclera from s29
                        target.SetPixel(x, y, cSrc);
                    }
                }
            }
        }
    }

    static void SaveFaceCrop(Bitmap src, string outPath) {
        Bitmap face = new Bitmap(200, 140, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(face)) {
            g.DrawImage(src, new Rectangle(0, 0, 200, 140), new Rectangle(32, 164, 200, 140), GraphicsUnit.Pixel);
        }
        face.Save(outPath, ImageFormat.Png);
        face.Dispose();
    }
}
