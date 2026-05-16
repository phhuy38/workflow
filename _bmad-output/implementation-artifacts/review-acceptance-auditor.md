You are an Acceptance Auditor. Review this diff against the spec and context docs. Check for: violations of acceptance criteria, deviations from spec intent, missing implementation of specified behavior, contradictions between spec constraints and actual code. Output findings as a Markdown list. Each finding: one-line title, which AC/constraint it violates, and evidence from the diff.

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

Spec:
# Story 3.3: Automatic step progression & process completion (FR12)
...
## Acceptance Criteria
1. Given một bước vừa được đánh dấu hoàn thành, When CompleteStep action được gọi, Then bước tiếp theo trong sequence tự động chuyển sang trạng thái 'pending', And deadline_at cho bước tiếp theo được tính từ thời điểm hiện tại + duration_hours.
2. Given bước cuối cùng trong instance vừa hoàn thành, When không còn bước nào pending, Then instance chuyển sang trạng thái 'completed', And completed_at được ghi nhận trên instance.
3. Given một bước được hoàn thành, When người phụ trách bước tiếp theo được xác định từ snapshot, Then nếu assignee_type là 'role', system chọn user có role đó (nếu có nhiều user, ưu tiên giải pháp đơn giản nhất cho MVP như random hoặc user đầu tiên lấy được) và gán assignee_id tương ứng cho StepExecution mới.
4. Given Manager override một bước (force-complete), When override được xác nhận, Then logic progression tương tự như complete bình thường — bước tiếp theo được kích hoạt.
...