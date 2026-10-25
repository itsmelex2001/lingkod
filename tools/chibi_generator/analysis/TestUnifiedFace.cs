using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class TestUnifiedFace5 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap s29 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.90.png"));
        Bitmap s30 = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_3.00.png"));

        Bitmap girlHalf = BlendUnifiedFace(girl, s29, 132, 64, 595, 117);
        Bitmap girlClosed = BlendUnifiedFace(girl, s30, 132, 64, 598, 121);

        girlHalf.Save(Path.Combine(assetsDir, "girl_blink_half.png"), ImageFormat.Png);
        girlClosed.Save(Path.Combine(assetsDir, "girl_blink_closed.png"), ImageFormat.Png);

        SaveFaceCrop(girl, Path.Combine(assetsDir, "unified5_0_open.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "unified5_1_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "unified5_2_closed.png"));

        Console.WriteLine("Saved unified5_0..2.png!");
    }

    static Bitmap BlendUnifiedFace(Bitmap baseGirl, Bitmap srcFrame, int capBaseX, int capBaseY, int capSrcX, int capSrcY) {
        Bitmap res = new Bitmap(baseGirl);
        int dx = capSrcX - capBaseX;
        int dy = capSrcY - capBaseY;

        // Covers entire eye sockets including corners: X 46..234, Y 198..296
        int minX = 46, maxX = 234;
        int minY = 198, maxY = 296;
        int feather = 8;

        for (int gy = minY; gy <= maxY; gy++) {
            for (int gx = minX; gx <= maxX; gx++) {
                Color cBase = baseGirl.GetPixel(gx, gy);

                // 1. Keep all eyebrows from baseGirl 100% pristine:
                // Eyebrow pixels are dark at gy < 218 (left brow: gx 60..105, right brow: gx 155..215)
                bool isBrow = (cBase.R < 100 && cBase.G < 100 && cBase.B < 100) && (gy < 218) && 
                              ((gx >= 60 && gx <= 105) || (gx >= 155 && gx <= 215));
                if (isBrow) continue;

                // 2. Keep black side hair and bangs completely untouched:
                bool isHair = (cBase.R < 55 && cBase.G < 55 && cBase.B < 55) && 
                              (gx < 52 || gx > 226 || gy < 205);
                if (isHair) continue;

                int sx = gx + dx;
                int sy = gy + dy;
                if (sx < 0 || sx >= srcFrame.Width || sy < 0 || sy >= srcFrame.Height) continue;

                Color cSrc = srcFrame.GetPixel(sx, sy);

                // Don't copy black hair from src over forehead skin
                if (gy < 210 && cSrc.R < 70 && cSrc.G < 70 && cSrc.B < 70) continue;

                int distLeft = gx - minX;
                int distRight = maxX - gx;
                int distTop = gy - minY;
                int distBottom = maxY - gy;

                int minDist = Math.Min(Math.Min(distLeft, distRight), Math.Min(distTop, distBottom));
                double alpha = 1.0;
                if (minDist < feather) {
                    alpha = (double)minDist / feather;
                }

                int r = (int)(cSrc.R * alpha + cBase.R * (1.0 - alpha));
                int g = (int)(cSrc.G * alpha + cBase.G * (1.0 - alpha));
                int b = (int)(cSrc.B * alpha + cBase.B * (1.0 - alpha));

                res.SetPixel(gx, gy, Color.FromArgb(cBase.A, r, g, b));
            }
        }
        return res;
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
