using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class BuildCleanBlinkFrames3 {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap f0 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_0.png"));
        Bitmap f1 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_1.png"));
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));
        Bitmap girl = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));

        int cropOriginX = 32;
        int cropOriginY = 164;

        Bitmap girlHalf = new Bitmap(girl);
        Bitmap girlClosed = new Bitmap(girl);

        // Offsets: f1 is (-2, +3), f2 is (-8, +10)
        ApplyEyeBlink(girlHalf, f1, cropOriginX, cropOriginY, -2, 3);
        ApplyEyeBlink(girlClosed, f2, cropOriginX, cropOriginY, -8, 10);

        girlHalf.Save(Path.Combine(assetsDir, "girl_blink_half.png"), ImageFormat.Png);
        girlClosed.Save(Path.Combine(assetsDir, "girl_blink_closed.png"), ImageFormat.Png);

        SaveFaceCrop(girl, Path.Combine(assetsDir, "clean_face_0_open.png"));
        SaveFaceCrop(girlHalf, Path.Combine(assetsDir, "clean_face_1_half.png"));
        SaveFaceCrop(girlClosed, Path.Combine(assetsDir, "clean_face_2_closed.png"));

        Console.WriteLine("Clean blink frames created with full eye socket coverage!");
    }

    static void ApplyEyeBlink(Bitmap girlTarget, Bitmap faceSource, int cropOriginX, int cropOriginY, int offX, int offY) {
        // Left eye: center (72, 74), rx: 36, ry: 40
        // Right eye: center (164, 74), rx: 35, ry: 40
        BlendEllipse(girlTarget, faceSource, cropOriginX, cropOriginY, 72, 74, 36, 40, offX, offY);
        BlendEllipse(girlTarget, faceSource, cropOriginX, cropOriginY, 164, 74, 35, 40, offX, offY);
    }

    static void BlendEllipse(Bitmap target, Bitmap source, int originX, int originY, int cx, int cy, int rx, int ry, int offX, int offY) {
        for (int dy = -ry - 2; dy <= ry + 2; dy++) {
            int py = cy + dy;
            int sy = py + offY;
            if (sy < 0 || sy >= source.Height) continue;
            int targetY = originY + py;
            if (targetY < 0 || targetY >= target.Height) continue;

            for (int dx = -rx - 2; dx <= rx + 2; dx++) {
                int px = cx + dx;
                int sx = px + offX;
                if (sx < 0 || sx >= source.Width) continue;
                int targetX = originX + px;
                if (targetX < 0 || targetX >= target.Width) continue;

                double dist = Math.Sqrt(Math.Pow((double)dx / rx, 2) + Math.Pow((double)dy / ry, 2));
                if (dist > 1.0) continue;

                double alpha = 1.0;
                if (dist > 0.82) {
                    alpha = (1.0 - dist) / 0.18;
                }

                Color cSrc = source.GetPixel(sx, sy);
                Color cTgt = target.GetPixel(targetX, targetY);

                int r = (int)(cSrc.R * alpha + cTgt.R * (1.0 - alpha));
                int g = (int)(cSrc.G * alpha + cTgt.G * (1.0 - alpha));
                int b = (int)(cSrc.B * alpha + cTgt.B * (1.0 - alpha));

                target.SetPixel(targetX, targetY, Color.FromArgb(cTgt.A, r, g, b));
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
