using System;
using System.Drawing;
using System.IO;

public class TrackHeadMotion {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        // Base patch from sec_2.00.png at forehead (X: 100+466=566, Y: 120+45=165)
        Bitmap baseFrame = new Bitmap(Path.Combine(assetsDir, @"scan_girl_fine\sec_2.00.png"));
        int patchX = 566, patchY = 165, patchW = 60, patchH = 60;
        
        string[] frames = new string[] {
            "sec_2.00.png", "sec_2.20.png", "sec_2.40.png", "sec_2.60.png", 
            "sec_2.80.png", "sec_2.90.png", "sec_3.00.png", "sec_3.10.png", "sec_3.20.png"
        };

        foreach (string fn in frames) {
            string p = Path.Combine(assetsDir, "scan_girl_fine", fn);
            if (!File.Exists(p)) continue;
            using (Bitmap f = new Bitmap(p)) {
                int bestDiff = int.MaxValue;
                int bestOffX = 0, bestOffY = 0;
                for (int oy = -25; oy <= 25; oy++) {
                    for (int ox = -25; ox <= 25; ox++) {
                        int diff = 0;
                        for (int py = 0; py < patchH; py += 3) {
                            for (int px = 0; px < patchW; px += 3) {
                                Color c1 = baseFrame.GetPixel(patchX + px, patchY + py);
                                Color c2 = f.GetPixel(patchX + px + ox, patchY + py + oy);
                                diff += Math.Abs(c1.R - c2.R) + Math.Abs(c1.G - c2.G) + Math.Abs(c1.B - c2.B);
                            }
                        }
                        if (diff < bestDiff) {
                            bestDiff = diff;
                            bestOffX = ox;
                            bestOffY = oy;
                        }
                    }
                }
                Console.WriteLine(string.Format("{0}: head offset vs sec_2.00 = ({1}, {2}), diff={3}", fn, bestOffX, bestOffY, bestDiff));
            }
        }
    }
}
