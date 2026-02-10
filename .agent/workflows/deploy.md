---
description: Automatically commit and deploy changes to the dev environment
---

1. Check git status to ensure there are changes to commit.
2. Run `git add .` to stage all changes.
   // turbo
3. Formulate a commit message based on the recent changes made in the task. The format should be: `feat: <summary of changes>` or `fix: <summary of changes>`.
   // turbo
4. Run `git commit -m "<your_commit_message>"`
5. Run `git push origin dev`
6. Notify the user that changes have been pushed to dev and the GitHub Action should be running.
