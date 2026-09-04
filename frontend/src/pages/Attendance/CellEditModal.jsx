import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Modal, Form, Select, Input, Descriptions } from 'antd';
import { attendanceService } from '../../services/attendanceService';
import RuleTracePanel from '../../components/shared/RuleTracePanel';
import CodeBadge from '../../components/shared/CodeBadge';

const CODE_OPTIONS = [
  'HO2', 'HOS2', 'HOA2', 'HO1', 'HOS1', 'HOA1', 'HAS', 'HS',
  '1901', '1902', '1903', '1904', '1905', '1906', 'SC1', 'SCB', '8', '7',
].map((c) => ({ value: c, label: c }));

export default function CellEditModal({ cell, open, onClose, onSave }) {
  const [form] = Form.useForm();

  const { data: traces } = useQuery({
    queryKey: ['cell-trace', cell?.id],
    queryFn: () => attendanceService.cells.trace(cell.id),
    enabled: !!cell?.id && open,
  });

  useEffect(() => {
    if (cell) {
      form.setFieldsValue({
        final_code: cell.final_code || cell.auto_code,
        override_reason: '',
      });
    }
  }, [cell, form]);

  return (
    <Modal
      title={`Ubah Sel: ${cell?.employeeName}`}
      open={open}
      onCancel={onClose}
      onOk={() => form.submit()}
      okText="Simpan"
      cancelText="Batal"
      width={560}
    >
      <Descriptions size="small" column={2} style={{ marginBottom: 16 }}>
        <Descriptions.Item label="Tanggal">{cell?.work_date}</Descriptions.Item>
        <Descriptions.Item label="Tipe Hari">{cell?.day_type}</Descriptions.Item>
        <Descriptions.Item label="Kode Otomatis">
          <CodeBadge code={cell?.auto_code} dayType={cell?.day_type} />
        </Descriptions.Item>
        <Descriptions.Item label="Kode Final">
          <CodeBadge code={cell?.final_code} isOverridden={cell?.is_overridden} dayType={cell?.day_type} />
        </Descriptions.Item>
      </Descriptions>

      <Form form={form} layout="vertical" onFinish={onSave}>
        <Form.Item name="final_code" label="Kode Override">
          <Select options={CODE_OPTIONS} allowClear showSearch placeholder="Cari kode" />
        </Form.Item>
        <Form.Item
          name="override_reason"
          label="Alasan Override"
          rules={[{ required: true, message: 'Wajib diisi' }]}
        >
          <Input.TextArea rows={2} placeholder="Mengapa kode ini diubah?" />
        </Form.Item>
      </Form>

      <h4 style={{ marginTop: 16 }}>Jejak Aturan</h4>
      <RuleTracePanel traces={traces} />
    </Modal>
  );
}
