using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;

public class MakeIdleSpritesheet {
    public static void Main() {
        string assetsDir = @"C:\xampp1\htdocs\sdn\public\assets";
        Bitmap fOpen = new Bitmap(Path.Combine(assetsDir, "chibi_girl_full.png"));
        Bitmap fHalf = new Bitmap(Path.Combine(assetsDir, "girl_blink_half.png"));
        Bitmap fClosed = new Bitmap(Path.Combine(assetsDir, "girl_blink_closed.png"));

        int w = fOpen.Width;  // 360
        int h = fOpen.Height; // 635

        // 4 frames: Open (0), Half (1), Closed (2), Half (3)
        Bitmap sheet = new Bitmap(w * 4, h, PixelFormat.Format32bppArgb);
        using (Graphics g = Graphics.FromImage(sheet)) {
            g.DrawImage(fOpen, 0 * w, 0);
            g.DrawImage(fHalf, 1 * w, 0);
            g.DrawImage(fClosed, 2 * w, 0);
            g.DrawImage(fHalf, 3 * w, 0);
        }

        sheet.Save(Path.Combine(assetsDir, "girl_idle_spritesheet.png"), ImageFormat.Png);
        Console.WriteLine(string.Format("Generated girl_idle_spritesheet.png ({0}x{1}) with 4 frames!", sheet.Width, sheet.Height));
    }
}
