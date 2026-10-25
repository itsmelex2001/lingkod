using System;
using System.Drawing;
using System.IO;

public class CorrelateFaces {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap f0 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_0.png"));
        Bitmap f1 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_1.png"));
        Bitmap f2 = new Bitmap(Path.Combine(assetsDir, "raw_video_face_2.png"));

        // Match using the left eyebrow + hair strand patch:
        // In f0: X 35..85, Y 15..35
        Point off1 = FindOffset(f0, f1, 35, 15, 50, 20);
        Point off2 = FindOffset(f0, f2, 35, 15, 50, 20);

        Console.WriteLine(string.Format("f1 offset relative to f0: dx={0}, dy={1}", off1.X, off1.Y));
        Console.WriteLine(string.Format("f2 offset relative to f0: dx={0}, dy={1}", off2.X, off2.Y));
    }

    static Point FindOffset(Bitmap bBase, Bitmap bTarget, int px, int py, int pw, int ph) {
        int bestDiff = int.MaxValue;
        int bestOx = 0, bestOy = 0;
        for (int oy = -15; oy <= 15; oy++) {
            for (int ox = -15; ox <= 15; ox++) {
                int diff = 0;
                for (int y = 0; y < ph; y++) {
                    for (int x = 0; x < pw; x++) {
                        Color c1 = bBase.GetPixel(px + x, py + y);
                        Color c2 = bTarget.GetPixel(px + x + ox, py + y + oy);
                        diff += Math.Abs(c1.R - c2.R) + Math.Abs(c1.G - c2.G) + Math.Abs(c1.B - c2.B);
                    }
                }
                if (diff < bestDiff) {
                    bestDiff = diff;
                    bestOx = ox;
                    bestOy = oy;
                }
            }
        }
        return new Point(bestOx, bestOy);
    }
}
