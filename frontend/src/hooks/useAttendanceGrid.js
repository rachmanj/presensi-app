import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { attendanceService } from '../services/attendanceService';

export function useAttendanceGrid(sheetId) {
  const queryClient = useQueryClient();

  const gridQuery = useQuery({
    queryKey: ['attendance-grid', sheetId],
    queryFn: () => attendanceService.sheets.grid(sheetId),
    enabled: !!sheetId,
  });

  const updateCell = useMutation({
    mutationFn: ({ cellId, final_code, override_reason }) =>
      attendanceService.cells.update(cellId, { final_code, override_reason }),
    onMutate: async ({ cellId, dayOfMonth, final_code }) => {
      await queryClient.cancelQueries({ queryKey: ['attendance-grid', sheetId] });
      const previous = queryClient.getQueryData(['attendance-grid', sheetId]);

      queryClient.setQueryData(['attendance-grid', sheetId], (old) => {
        if (!old) return old;
        return {
          ...old,
          rows: old.rows.map((row) => {
            const cell = row.cells[dayOfMonth];
            if (!cell || cell.id !== cellId) return row;
            return {
              ...row,
              cells: {
                ...row.cells,
                [dayOfMonth]: { ...cell, final_code, is_overridden: true },
              },
            };
          }),
        };
      });

      return { previous };
    },
    onError: (_err, _vars, context) => {
      if (context?.previous) {
        queryClient.setQueryData(['attendance-grid', sheetId], context.previous);
      }
    },
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: ['attendance-grid', sheetId] });
    },
  });

  return { ...gridQuery, updateCell };
}
