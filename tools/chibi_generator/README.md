# Chibi Chatbot Sprite Generation & Asset Pipeline

This directory contains the tools and scripts used to create, extract, analyze, and assemble the chibi character spritesheets for the LINKod website chatbot (**Nurse Mika** and **Dr. Leo**).

---

## Directory Overview

### 1. `generators/` (Production Spritesheet Builders)
These C# scripts generate and composite the final spritesheets used in the frontend:
- **`MakeIdleSpritesheet.cs`**: Combines open, half-closed, and closed eye frames to generate `girl_idle_spritesheet.png` (4 frames for smooth natural blinking).
- **`UpdateWalkSheet.cs`**: Injects realistic multi-frame blinking cycles into `girl_walk_spritesheet.png`.
- **`CreatePerfectBlinkSprites.cs`**: Performs facial eye-socket color matching, skin blending, and eyelash overlays to produce `girl_blink_half.png` and `girl_blink_closed.png`.
- **`BuildCleanBlinkFrames.cs`**: Alternative clean blink frame generator using elliptical eye socket masking.
- **`CropRawFaces.cs`**: Extracts face crops from extracted video keyframes.
- **`ExtractOriginalBlinks.cs`**: Extracts original blink frames from video sequences.
- **`PerfectBlinkComposite.cs`**: Composites fine blink frames onto character body bases.

### 2. `extractors/` (Video Frame Extraction Scripts)
PowerShell and FFmpeg scripts that extract raw video frames and animations from source MP4s:
- **`extract_walk_cycle.ps1`**: Extracts walking cycle frames from `chatbot_chibi(girl).MP4`.
- **`extract_girl.ps1`**: Extracts high-resolution character animation frames.
- **`extract_fine.ps1`**: Precision millisecond frame extractor for blink transitions.
- **`crop_assets.ps1`**: Crops character bounding boxes and removes background.
- **`extract_entrance.ps1`**: Extracts walk-in/entrance frames.
- **`extract_turn.ps1`**: Extracts character turn animations.
- **`extract_bg_girl.ps1`**: Extracts standing/idle frames.
- **`scan_video.ps1`**: Scans video keyframes at regular intervals.
- **`inspect_girl_frames.ps1`**: Validates frame counts and dimensions.
- **`check_hair.ps1`**: Inspects hairline and head bounds across frames.

### 3. `analysis/` (Facial Alignment & Blend Experiments)
Consolidated analytical and diagnostic scripts used during sprite refinement:
- **`TestSeamlessBlink.cs`**: Tests seamless closed-eye synthesis by replacing sclera/pupils and overlaying curved lashes.
- **`TestUnifiedFace.cs`**: Tests unified face blending with skin smoothing.
- **`TestFeatheredFace.cs`**: Tests edge feathering and alpha blending for face transitions.
- **`TestTwoEyeBoxes.cs`**: Tests isolated dual-box eye replacement boundaries.
- **`TestFaceInHair.cs`**: Tests face positioning behind hair bangs and cap.
- **`AnalyzeEyeRegion.cs`**: Scans and measures eye coordinates & socket bounding boxes.
- **`TrackHeadMotion.cs`**: Measures vertical/horizontal head sway between walk frames.
- **`FindLashes.cs`**: Detects lash contours and curvature.
- **`MeasureColorDiff.cs`**: Compares RGB delta between video captures and static sprites.
- **`CorrelateFaces.cs`**: Correlates facial landmark coordinates.
- **`InspectFineBlinks.cs`**: Inspects sub-pixel blink frames.
- **`analyze_girl_walk.php`**: PHP script that analyzes character foot planting and walking bounce.

---

## How to Compile & Run C# Generators

To compile any `.cs` script using the built-in .NET Framework C# compiler on Windows:

```powershell
# Example: Compile MakeIdleSpritesheet
& "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe" /r:System.Drawing.dll generators\MakeIdleSpritesheet.cs

# Run the compiled executable
.\generators\MakeIdleSpritesheet.exe
```

Compiled `.exe` files are disposable build artifacts and do not need to be committed to version control.
