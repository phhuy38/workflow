You are an Edge Case Hunter code reviewer. Review the following diff. You have read access to the project if you need more context. Find edge cases, state machine violations, and unexpected integration failures that might break this code. Output findings as a Markdown list.

Diff:
```diff
diff --git a/app/Actions/Process/AdvanceProcessInstance.php b/app/Actions/Process/AdvanceProcessInstance.php
index 3fec29a..f865294 100644
--- a/app/Actions/Process/AdvanceProcessInstance.php
+++ b/app/Actions/Process/AdvanceProcessInstance.php
@@ -32,7 +32,7 @@ public function handle(ProcessInstance $instance, StepExecution $currentStep, Us
                 'name' => $nextStepDef['name'],
                 'order' => $nextStepDef['order'],
                 'status' => 'pending',
-                'assigned_to' => $this->resolveAssignee->handle($nextStepDef),
+                'assigned_to' => $this->resolveAssignee->handle($nextStepDef) ?? $instance->launched_by,
                 'deadline_at' => now()->addHours($nextStepDef['duration_hours']),
             ]);
 
diff --git a/app/Actions/Process/CompleteStep.php b/app/Actions/Process/CompleteStep.php
index 7879091..e4823c6 100644
--- a/app/Actions/Process/CompleteStep.php
+++ b/app/Actions/Process/CompleteStep.php
@@ -31,6 +31,9 @@ public function handle(StepExecution $step, User $user, array $data = []): void
                 ->log('completed');
 
             $this->advanceProcess->handle($step->instance, $step, $user);
+
+            event(new \App\Events\StepExecutionUpdated($step));
+            event(new \App\Events\ProcessInstanceUpdated($step->instance));
         });
     }
 }
diff --git a/app/Actions/Process/LaunchProcessInstance.php b/app/Actions/Process/LaunchProcessInstance.php
index f09be19..8c66e1b 100644
--- a/app/Actions/Process/LaunchProcessInstance.php
+++ b/app/Actions/Process/LaunchProcessInstance.php
@@ -58,7 +58,7 @@ public function handle(ProcessTemplate $template, array $data, User $launcher):
                     'name' => $firstStepDef->name,
                     'order' => $firstStepDef->order,
                     'status' => 'pending',
-                    'assigned_to' => $this->resolveAssignee->handle($firstStepDef->toArray()),
+                    'assigned_to' => $this->resolveAssignee->handle($firstStepDef->toArray()) ?? $instance->launched_by,
                     'deadline_at' => now()->addHours($firstStepDef->duration_hours),
                 ]);
             }
```
